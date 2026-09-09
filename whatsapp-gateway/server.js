const express = require('express');
const qrcode = require('qrcode');
const fs = require('fs');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const { LoadUtils } = require('whatsapp-web.js/src/util/Injected/Utils');
const { execSync } = require('child_process');

const PORT = Number(process.env.WHATSAPP_GATEWAY_PORT || 3001);
const TOKEN = process.env.WHATSAPP_GATEWAY_TOKEN || 'wasla-local-token';

const app = express();
app.use(express.json({ limit: '1mb' }));

const state = {
    ready: false,
    qrDataUrl: null,
    lastError: null,
    authenticated: false,
    loadingPercent: null,
};

let client;

const path = require('path');

const PUBLIC_ROOT = path.resolve(__dirname, '..', 'public');

function resolveChromePath() {
    if (process.env.CHROME_PATH && fs.existsSync(process.env.CHROME_PATH)) {
        return process.env.CHROME_PATH;
    }

    const candidates = [
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        process.env.LOCALAPPDATA ? `${process.env.LOCALAPPDATA}\\Google\\Chrome\\Application\\chrome.exe` : null,
    ].filter(Boolean);

    return candidates.find((candidate) => fs.existsSync(candidate)) || undefined;
}

function resolveLocalImage(filePath) {
    if (!filePath) {
        return null;
    }

    const resolved = path.resolve(String(filePath));
    if (!resolved.startsWith(PUBLIC_ROOT)) {
        return null;
    }

    return fs.existsSync(resolved) ? resolved : null;
}

function extractInviteCode(input) {
    const value = String(input || '').trim();
    const match = value.match(/chat\.whatsapp\.com\/([A-Za-z0-9_-]+)/i);
    return match ? match[1] : value;
}

async function resolveGroupFromInvite(inviteInput) {
    const code = extractInviteCode(inviteInput);
    if (!code) {
        throw new Error('invalid_invite_code');
    }

    let info = null;
    try {
        info = await client.getInviteInfo(code);
    } catch (error) {
        console.warn('[whatsapp-gateway] getInviteInfo:', error.message);
    }

    const chatId =
        info?.gid?._serialized ||
        info?.gid ||
        info?.id?._serialized ||
        info?.id ||
        null;

    if (chatId && String(chatId).endsWith('@g.us')) {
        return { chatId: String(chatId), name: info?.subject || info?.name || null };
    }

    try {
        const joinedId = await client.acceptInvite(code);
        if (joinedId && String(joinedId).endsWith('@g.us')) {
            return { chatId: String(joinedId), name: info?.subject || null };
        }
    } catch (error) {
        console.warn('[whatsapp-gateway] acceptInvite:', error.message);

        const subject = info?.subject || info?.name;
        if (subject) {
            const groups = await fetchGroupsList();
            const match = groups.find((group) => {
                const a = String(group.name).toLowerCase().trim();
                const b = String(subject).toLowerCase().trim();

                return a === b || a.includes(b) || b.includes(a);
            });

            if (match) {
                return { chatId: match.id, name: match.name };
            }
        }
    }

    throw new Error('could_not_resolve_group_from_invite');
}

async function fetchGroupsList() {
    const attempts = [];
    await reinjectWWebJS();

    for (let i = 0; i < 3; i++) {
        if (i > 0) {
            await new Promise((r) => setTimeout(r, 3000));
        }

        try {
            const chats = await client.getChats();
            const groups = chats
                .filter((chat) => chat.isGroup && chat.id && chat.id._serialized)
                .map((chat) => ({
                    id: chat.id._serialized,
                    name: chat.name || 'Unnamed group',
                }));

            if (groups.length > 0) {
                return groups;
            }

            attempts.push('getChats: empty');
        } catch (error) {
            attempts.push(`getChats: ${error.message}`);
        }

        try {
            const groups = await client.pupPage.evaluate(() => {
                const collections = window.require?.('WAWebCollections');
                const chats = collections?.Chat?.getModelsArray?.() || [];
                return chats
                    .filter((chat) => chat.id?._serialized && (chat.groupMetadata || chat.isGroup))
                    .map((chat) => ({
                        id: chat.id._serialized,
                        name: chat.name || chat.formattedTitle || 'Unnamed group',
                    }));
            });

            if (groups.length > 0) {
                return groups;
            }

            attempts.push('evaluate: empty');
        } catch (error) {
            attempts.push(`evaluate: ${error.message}`);
        }
    }

    console.warn('[whatsapp-gateway] fetchGroupsList attempts:', attempts.join(' | '));
    return [];
}

let sendQueue = Promise.resolve();

function enqueueSend(task) {
    const run = sendQueue.then(() => task());
    sendQueue = run.catch(() => {});
    return run;
}

function delay(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

const CAPTION_MAX = 900;
let recovering = null;

function isTransientSendError(error) {
    const message = String(error?.message || error || '');
    return /Protocol error|Promise was collected|Execution context|detached|Target closed|Session closed|not_ready|getChat|getChats|WWebJS|Cannot read properties of undefined/i.test(message);
}

function friendlySendError(error) {
    const message = String(error?.message || error || '');
    if (/not_ready/i.test(message)) {
        return new Error('not_ready');
    }
    if (/detached|Target closed|Session closed|Execution context|Protocol error|getChat|getChats|WWebJS|Cannot read properties of undefined/i.test(message)) {
        return new Error('session_detached');
    }

    return error instanceof Error ? error : new Error(message);
}

function shortCaption(message) {
    const lines = String(message || '').split('\n');
    const name = lines[0] || '';
    const urlLine = lines.find((line) => line.startsWith('🔗')) || '';
    const footer = lines.find((line) => line.includes('اطلب')) || '';
    const caption = [name, urlLine, footer].filter(Boolean).join('\n\n');

    return caption.slice(0, CAPTION_MAX);
}

async function injectionReady() {
    try {
        const page = client?.pupPage;
        if (!page || page.isClosed()) {
            return false;
        }

        return await page.evaluate(() => typeof window.WWebJS?.getChat === 'function');
    } catch {
        return false;
    }
}

async function pageIsUsable() {
    return injectionReady();
}

async function reinjectWWebJS() {
    const page = client?.pupPage;
    if (!page || page.isClosed()) {
        return false;
    }

    if (await injectionReady()) {
        return true;
    }

    try {
        const hasModules = await page.evaluate(() => typeof window.require === 'function');
        if (hasModules) {
            await page.evaluate(LoadUtils);
            if (await injectionReady()) {
                console.log('[whatsapp-gateway] re-injected WWebJS helpers');
                return true;
            }
        }
    } catch (error) {
        console.warn('[whatsapp-gateway] reinject failed:', error.message);
    }

    return false;
}

async function waitUntilReady(timeoutMs = 45000) {
    const start = Date.now();
    while (Date.now() - start < timeoutMs) {
        if (client && (await injectionReady())) {
            state.ready = true;
            state.lastError = null;
            return;
        }

        if (client && state.ready && (await reinjectWWebJS())) {
            state.lastError = null;
            return;
        }

        if (state.qrDataUrl) {
            throw new Error('not_ready');
        }
        await delay(500);
    }

    throw new Error('session_detached');
}

async function recoverFromDetached() {
    if (recovering) {
        return recovering;
    }

    recovering = (async () => {
        console.warn('[whatsapp-gateway] recovering WhatsApp session...');
        state.ready = false;

        if (await reinjectWWebJS()) {
            state.ready = true;
            state.lastError = null;
            return;
        }

        await resetClient();
        createClient();
        await waitUntilReady(60000);
    })().finally(() => {
        recovering = null;
    });

    return recovering;
}

async function resolveChatId(chatId) {
    if (!String(chatId).endsWith('@c.us')) {
        return chatId;
    }

    const digits = String(chatId).replace(/@c\.us$/, '');
    try {
        const numberId = await client.getNumberId(digits);
        if (numberId?._serialized) {
            return numberId._serialized;
        }
    } catch (error) {
        console.warn('[whatsapp-gateway] getNumberId:', error.message);
    }

    return chatId;
}

async function sendWithRetry(task, retries = 4) {
    let lastError = null;

    for (let attempt = 0; attempt < retries; attempt++) {
        try {
            if (attempt > 0) {
                await delay(1200 * attempt);
            }

            await waitUntilReady(20000);

            return await task();
        } catch (error) {
            lastError = error;
            if (!isTransientSendError(error) || attempt === retries - 1) {
                throw friendlySendError(error);
            }

            console.warn(`[whatsapp-gateway] send retry ${attempt + 1}/${retries}:`, error.message);
            try {
                await recoverFromDetached();
            } catch (recoverError) {
                throw friendlySendError(recoverError);
            }
        }
    }

    throw friendlySendError(lastError);
}

async function deliverMessage(chatId, message, localImage, image) {
    chatId = await resolveChatId(chatId);
    const caption = String(message || '').slice(0, CAPTION_MAX);
    const sendOptions = { caption, sendSeen: false };
    const sendCaptioned = (media) => client.sendMessage(chatId, media, sendOptions);
    const sendText = () => client.sendMessage(chatId, caption, { sendSeen: false });

    if (localImage) {
        const media = MessageMedia.fromFilePath(localImage);
        try {
            await sendWithRetry(() => sendCaptioned(media));
            return;
        } catch (error) {
            console.warn('[whatsapp-gateway] image+caption failed, sending image only:', error.message);
            try {
                await sendWithRetry(() => client.sendMessage(chatId, media, { caption: shortCaption(caption) }));
                return;
            } catch {
                await sendWithRetry(sendText);
                return;
            }
        }
    }

    if (image && /^https?:\/\//i.test(image)) {
        try {
            const media = await MessageMedia.fromUrl(image, { unsafeMime: true });
            await sendWithRetry(() => sendCaptioned(media));
            return;
        } catch (error) {
            console.warn('[whatsapp-gateway] remote image failed, falling back to text:', error.message);
            await sendWithRetry(sendText);
            return;
        }
    }

    await sendWithRetry(sendText);
}

function normalizePhone(phone) {
    const digits = String(phone || '').replace(/\D/g, '');
    if (!digits) {
        return null;
    }

    return `${digits}@c.us`;
}

function auth(req, res, next) {
    const token = req.headers['x-gateway-token'] || req.body?.token;
    if (token !== TOKEN) {
        return res.status(401).json({ ok: false, error: 'unauthorized' });
    }

    return next();
}

function killOrphanChrome() {
    if (process.platform === 'win32') {
        try {
            execSync(
                "powershell -NoProfile -Command \"Get-CimInstance Win32_Process -Filter \\\"name='chrome.exe'\\\" -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like '*wwebjs_auth*' } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }\"",
                { stdio: 'ignore' },
            );
        } catch {
            // no matching chrome processes
        }
    }

    const sessionDir = path.join(__dirname, '.wwebjs_auth', 'session');
    for (const name of ['DevToolsActivePort', 'SingletonLock', 'SingletonCookie', 'SingletonSocket']) {
        try {
            fs.unlinkSync(path.join(sessionDir, name));
        } catch {
            // lock file already gone
        }
    }
}

function clearAuthData() {
    killOrphanChrome();
    for (const dir of ['.wwebjs_auth', '.wwebjs_cache']) {
        const full = path.join(__dirname, dir);
        if (!fs.existsSync(full)) {
            continue;
        }
        try {
            fs.rmSync(full, { recursive: true, force: true, maxRetries: 3, retryDelay: 500 });
            console.log('[whatsapp-gateway] cleared', dir);
        } catch (error) {
            console.warn('[whatsapp-gateway] could not clear', dir, error.message);
        }
    }
}

let creatingClient = false;

async function resetClient() {
    const old = client;
    client = null;
    state.ready = false;
    state.authenticated = false;
    state.loadingPercent = null;
    state.qrDataUrl = null;

    try {
        if (old?.pupBrowser) {
            await old.pupBrowser.close();
        }
    } catch {
        // browser already gone
    }

    try {
        if (old) {
            await old.destroy();
        }
    } catch {
        // client already destroyed
    }

    killOrphanChrome();
    await delay(2000);
}

function createClient() {
    if (client || creatingClient) {
        return client;
    }

    creatingClient = true;
    state.lastError = null;

    client = new Client({
        authStrategy: new LocalAuth({ dataPath: './.wwebjs_auth' }),
        userAgent:
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        deviceName: 'Windows',
        browserName: 'Chrome',
        puppeteer: {
            headless: 'new',
            executablePath: resolveChromePath(),
            args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
        },
    });

    client.on('qr', async (qr) => {
        state.ready = false;
        state.authenticated = false;
        state.lastError = null;
        state.qrDataUrl = await qrcode.toDataURL(qr);
        console.log('[whatsapp-gateway] QR ready — scan from admin connection page');
    });

    client.on('authenticated', () => {
        state.authenticated = true;
        state.qrDataUrl = null;
        console.log('[whatsapp-gateway] authenticated — session still linked on the phone, waiting for WhatsApp Web ready');
    });

    client.on('loading_screen', (percent) => {
        state.loadingPercent = percent;
        console.log('[whatsapp-gateway] loading WhatsApp Web', percent);
    });

    client.on('ready', () => {
        state.ready = true;
        state.authenticated = true;
        state.qrDataUrl = null;
        state.lastError = null;
        state.loadingPercent = 100;
        console.log('[whatsapp-gateway] connected');
    });

    client.on('auth_failure', async (message) => {
        state.ready = false;
        state.authenticated = false;
        state.lastError = String(message || 'auth_failure');
        console.error('[whatsapp-gateway] auth failure:', state.lastError);
        await resetClient();
        clearAuthData();
        scheduleReconnect(3000);
    });

    client.on('disconnected', async (reason) => {
        state.ready = false;
        state.authenticated = false;
        state.lastError = String(reason || 'disconnected');
        console.warn('[whatsapp-gateway] disconnected:', state.lastError);
        await resetClient();
        if (String(reason).toUpperCase().includes('LOGOUT')) {
            clearAuthData();
        }
        scheduleReconnect(3000);
    });

    client.initialize().catch(async (error) => {
        creatingClient = false;
        state.lastError = error.message;
        console.error('[whatsapp-gateway] init error:', error.message);
        await resetClient();
        const locked = /already running|userDataDir/i.test(String(error.message || ''));
        if (locked) {
            killOrphanChrome();
        }
        scheduleReconnect(locked ? 8000 : 5000);
    });

    creatingClient = false;
    return client;
}

let reconnectTimer = null;

function scheduleReconnect(delayMs = 5000) {
    if (reconnectTimer) {
        return;
    }
    reconnectTimer = setTimeout(() => {
        reconnectTimer = null;
        try {
            createClient();
        } catch (error) {
            state.lastError = error.message;
            scheduleReconnect(8000);
        }
    }, delayMs);
}

process.on('uncaughtException', async (error) => {
    state.lastError = error.message;
    console.error('[whatsapp-gateway] uncaught:', error.message);
    await resetClient();
    scheduleReconnect(8000);
});

process.on('unhandledRejection', (reason) => {
    const message = reason instanceof Error ? reason.message : String(reason);
    state.lastError = message;
    console.error('[whatsapp-gateway] unhandled rejection:', message);
});

app.get('/health', (_req, res) => {
    res.json({ ok: true });
});

app.get('/status', auth, (_req, res) => {
    res.json({
        ok: true,
        ready: state.ready,
        qr: state.qrDataUrl,
        error: state.lastError,
        authenticated: state.authenticated,
        loadingPercent: state.loadingPercent,
        initializing: !state.ready && !state.qrDataUrl && !state.lastError,
    });
});

app.post('/reset', auth, async (_req, res) => {
    await resetClient();
    clearAuthData();
    createClient();
    res.json({ ok: true, message: 'session_reset' });
});

app.post('/send', auth, async (req, res) => {
    if (!state.ready || !client) {
        return res.status(503).json({ ok: false, error: 'not_ready' });
    }

    const target = String(req.body?.target || 'phone').trim();
    const message = String(req.body?.message || '').trim();
    const image = req.body?.image ? String(req.body.image) : null;
    const localImage = resolveLocalImage(req.body?.localImage);

    let chatId = null;
    if (target === 'group') {
        chatId = String(req.body?.groupChatId || '').trim();
        if (!chatId.endsWith('@g.us')) {
            return res.status(422).json({ ok: false, error: 'invalid_group_chat_id' });
        }
    } else {
        chatId = normalizePhone(req.body?.phone);
        if (!chatId) {
            return res.status(422).json({ ok: false, error: 'phone_required' });
        }
    }

    if (!message) {
        return res.status(422).json({ ok: false, error: 'message_required' });
    }

    try {
        await enqueueSend(() => deliverMessage(chatId, message, localImage, image));

        return res.json({ ok: true, target, chatId });
    } catch (error) {
        state.lastError = error.message;
        return res.status(500).json({ ok: false, error: error.message });
    }
});

app.get('/groups', auth, async (_req, res) => {
    if (!state.ready || !client) {
        return res.status(503).json({ ok: false, error: 'not_ready', groups: [] });
    }

    try {
        const groups = await fetchGroupsList();
        groups.sort((a, b) => String(a.name).localeCompare(String(b.name), 'ar'));

        return res.json({ ok: true, groups });
    } catch (error) {
        const message = error?.message || String(error);
        state.lastError = message;
        console.error('[whatsapp-gateway] groups error:', message);
        return res.status(500).json({ ok: false, error: message, groups: [] });
    }
});

app.post('/groups/resolve-invite', auth, async (req, res) => {
    if (!state.ready || !client) {
        return res.status(503).json({ ok: false, error: 'not_ready' });
    }

    const inviteLink = String(req.body?.inviteLink || req.body?.invite_link || '').trim();
    if (!inviteLink) {
        return res.status(422).json({ ok: false, error: 'invite_link_required' });
    }

    try {
        const resolved = await resolveGroupFromInvite(inviteLink);

        return res.json({
            ok: true,
            chatId: resolved.chatId,
            name: resolved.name,
        });
    } catch (error) {
        const message = error?.message || String(error);
        state.lastError = message;
        console.error('[whatsapp-gateway] resolve-invite error:', message);
        return res.status(500).json({ ok: false, error: message });
    }
});

createClient();

app.listen(PORT, () => {
    console.log(`[whatsapp-gateway] listening on http://127.0.0.1:${PORT}`);
});