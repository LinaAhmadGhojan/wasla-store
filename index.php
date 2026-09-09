<?php

/**
 * Shared-hosting entrypoint when the web root is the project root
 * (not public/). Forwards to Laravel's real front controller.
 * Works with any domain — set APP_URL in .env only.
 */
require __DIR__.'/public/index.php';
