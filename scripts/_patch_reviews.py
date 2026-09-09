from pathlib import Path

p = Path(r"E:/erp/wasla-store/resources/js/components/storefront/ProductPage.vue")
t = p.read_text(encoding="utf-8")

replacements = [
(
'''              <p v-if="r.body" class="review-text">«{{ r.body }}»</p>
              <a v-if="r.image_url" :href="r.image_url" target="_blank" class="review-photo">
                <img :src="r.image_url" alt="صورة التقييم" />
              </a>''',
'''              <p v-if="r.body" class="review-text">«{{ r.body }}»</p>
              <p v-if="r.fit_label" class="fit-tag">القياس: {{ r.fit_label }}</p>
              <div v-if="r.image_urls?.length" class="review-photos">
                <a v-for="(url, i) in r.image_urls" :key="i" :href="url" target="_blank" class="review-photo">
                  <img :src="url" alt="" />
                </a>
              </div>
              <a v-else-if="r.image_url" :href="r.image_url" target="_blank" class="review-photo">
                <img :src="r.image_url" alt="صورة التقييم" />
              </a>
              <video v-if="r.video_url" class="review-video" :src="r.video_url" controls playsinline></video>'''
),
(
'''          <textarea v-model="reviewForm.body" rows="3" placeholder="اكتبي رأيك بالمنتج..." maxlength="2000"></textarea>
          <label class="file-label">
            صورة (اختياري)
            <input type="file" accept="image/*" @change="onReviewImage" />
          </label>
          <p v-if="reviewForm.imageName" class="file-name">{{ reviewForm.imageName }}</p>''',
'''          <p class="fit-q">How was the fit? · كيف كان القياس؟</p>
          <div class="fit-pick">
            <button type="button" class="fit-btn" :class="{ on: reviewForm.fit_feedback === 'too_small' }" @click="reviewForm.fit_feedback = 'too_small'">Too Small</button>
            <button type="button" class="fit-btn" :class="{ on: reviewForm.fit_feedback === 'perfect' }" @click="reviewForm.fit_feedback = 'perfect'">Perfect</button>
            <button type="button" class="fit-btn" :class="{ on: reviewForm.fit_feedback === 'too_large' }" @click="reviewForm.fit_feedback = 'too_large'">Too Large</button>
          </div>
          <textarea v-model="reviewForm.body" rows="3" placeholder="اكتبي رأيك بالمنتج..." maxlength="2000"></textarea>
          <label class="file-label">
            صور (اختياري)
            <input type="file" accept="image/*" multiple @change="onReviewImages" />
          </label>
          <label class="file-label">
            فيديو (اختياري)
            <input type="file" accept="video/mp4,video/webm,video/quicktime" @change="onReviewVideo" />
          </label>
          <p v-if="reviewForm.imageName" class="file-name">{{ reviewForm.imageName }}</p>'''
),
(
'''const reviewForm = ref({
  rating: 5,
  body: '',
  image: null,
  imageName: '',
});''',
'''const reviewForm = ref({
  rating: 5,
  body: '',
  fit_feedback: 'perfect',
  image: null,
  images: [],
  video: null,
  imageName: '',
});'''
),
(
'''function onReviewImage(e) {
  const file = e.target.files?.[0] || null;
  reviewForm.value.image = file;
  reviewForm.value.imageName = file?.name || '';
}''',
'''function onReviewImage(e) {
  const file = e.target.files?.[0] || null;
  reviewForm.value.image = file;
  reviewForm.value.imageName = file?.name || '';
}
function onReviewImages(e) {
  const files = Array.from(e.target.files || []).slice(0, 5);
  reviewForm.value.images = files;
  reviewForm.value.image = files[0] || null;
  reviewForm.value.imageName = files.map((f) => f.name).join(', ');
}
function onReviewVideo(e) {
  reviewForm.value.video = e.target.files?.[0] || null;
}'''
),
]

for i, (old, new) in enumerate(replacements):
    if old not in t:
        raise SystemExit(f'missing block {i}')
    t = t.replace(old, new, 1)

# submitReview body check + formdata
old = """  if (!reviewForm.value.body.trim() && !reviewForm.value.image) {
    reviewFeedbackError.value = true;
    reviewFeedback.value = 'اكتبي تعليقاً أو ارفعي صورة.';
    return;
  }"""
new = """  if (!reviewForm.value.body.trim() && !reviewForm.value.image && !(reviewForm.value.images || []).length && !reviewForm.value.video) {
    reviewFeedbackError.value = true;
    reviewFeedback.value = 'اكتبي تعليقاً أو ارفعي صورة/فيديو.';
    return;
  }"""
if old not in t:
    raise SystemExit('missing submit check')
t = t.replace(old, new, 1)

old = """    if (reviewForm.value.body.trim()) fd.append('body', reviewForm.value.body.trim());
    if (reviewForm.value.image) fd.append('image', reviewForm.value.image);"""
new = """    if (reviewForm.value.body.trim()) fd.append('body', reviewForm.value.body.trim());
    if (reviewForm.value.fit_feedback) fd.append('fit_feedback', reviewForm.value.fit_feedback);
    if (reviewForm.value.image) fd.append('image', reviewForm.value.image);
    (reviewForm.value.images || []).forEach((file) => fd.append('images[]', file));
    if (reviewForm.value.video) fd.append('video', reviewForm.value.video);"""
if old not in t:
    raise SystemExit('missing formdata')
t = t.replace(old, new, 1)

old = """    reviewForm.value = { rating: 5, body: '', image: null, imageName: '' };"""
new = """    reviewForm.value = { rating: 5, body: '', fit_feedback: 'perfect', image: null, images: [], video: null, imageName: '' };"""
if old not in t:
    raise SystemExit('missing reset')
t = t.replace(old, new, 1)

# styles
needle = ".review-photo img { width: 100%; display: block; object-fit: cover; aspect-ratio: 1; }"
extra = """
.fit-tag { margin: 0.35rem 0 0; color: #1c7282; font-weight: 800; font-size: 0.85rem; }
.review-photos { display: flex; flex-wrap: wrap; gap: 0.45rem; margin-top: 0.5rem; }
.review-video { width: 100%; max-height: 240px; margin-top: 0.55rem; border-radius: 0.75rem; background: #000; }
.fit-q { margin: 0.5rem 0 0.4rem; font-weight: 800; color: #0b3d44; font-size: 0.9rem; }
.fit-pick { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 0.75rem; }
.fit-btn {
  border: 1.5px solid rgba(28,114,130,.25); background: #fff; color: #0b3d44;
  border-radius: 999px; padding: 0.4rem 0.75rem; font-weight: 800; font-size: 0.8rem; cursor: pointer;
}
.fit-btn.on { background: #1c7282; color: #fff; border-color: #1c7282; }
"""
if needle not in t:
    raise SystemExit('missing style needle')
t = t.replace(needle, needle + extra, 1)

p.write_text(t, encoding="utf-8")
print("OK")
