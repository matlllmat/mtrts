<?php
// modules/feedback/submit.view.php
// View for the feedback submission page.
// Receives: $wo, $existing_feedback, $submitted, $error

// Set $page so header.php has a non-empty value; then override $page_title
// after header.php runs so the navbar breadcrumb shows the correct title.
$page = 'feedback';
require_once __DIR__ . '/../../includes/header.php';
$page_title = 'Submit Feedback — ' . htmlspecialchars($wo['wo_number']);
require_once __DIR__ . '/../../includes/navbar.php';
?>

<!-- Page container -->
<div class="max-w-2xl mx-auto">

  <!-- Header card -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-5 mb-5">
    <h2 class="text-xl font-bold text-gray-900 tracking-tight mb-1">Submit Feedback</h2>
    <p class="text-sm text-gray-500">Share your experience with the completed repair job.</p>
  </div>

  <!-- Work Order details card -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-5 mb-5">
    <h3 class="text-sm font-bold text-gray-700 mb-3">Work Order Details</h3>
    <div class="space-y-2 text-sm">
      <div class="flex items-start gap-2">
        <span class="text-gray-500 font-medium min-w-[120px]">Work Order:</span>
        <span class="text-gray-900 font-semibold"><?= htmlspecialchars($wo['wo_number']) ?></span>
      </div>
      <div class="flex items-start gap-2">
        <span class="text-gray-500 font-medium min-w-[120px]">Ticket:</span>
        <span class="text-gray-900"><?= htmlspecialchars($wo['ticket_title']) ?></span>
      </div>
      <div class="flex items-start gap-2">
        <span class="text-gray-500 font-medium min-w-[120px]">Technician:</span>
        <span class="text-gray-900"><?= htmlspecialchars($wo['assigned_to_name'] ?? 'Unassigned') ?></span>
      </div>
    </div>
  </div>

  <?php if ($existing_feedback): ?>
    <!-- Existing feedback (read-only) -->
    <div class="bg-green-50 rounded-xl border border-green-200 px-6 py-5">
      <div class="flex items-start gap-3 mb-4">
        <svg class="w-6 h-6 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
          <h3 class="text-base font-bold text-green-900">Thank you for your feedback!</h3>
          <p class="text-sm text-green-700 mt-0.5">You submitted your rating on <?= date('M j, Y', strtotime($existing_feedback['submitted_at'])) ?>.</p>
        </div>
      </div>

      <!-- Read-only star display -->
      <div class="bg-white rounded-lg px-4 py-4 border border-green-100">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Your Rating</p>
        <div class="flex items-center gap-1 mb-4">
          <?php
          $rating = (int)$existing_feedback['rating'];
          for ($i = 1; $i <= 5; $i++):
            $filled = $i <= $rating;
          ?>
            <svg class="w-7 h-7 <?= $filled ? 'text-yellow-400' : 'text-gray-200' ?>" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
          <?php endfor; ?>
          <span class="ml-2 text-sm font-semibold text-gray-700"><?= $rating ?> out of 5</span>
        </div>

        <?php if (!empty($existing_feedback['comment'])): ?>
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Your Comment</p>
          <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($existing_feedback['comment']) ?></p>
        <?php endif; ?>
      </div>
    </div>

  <?php elseif ($submitted): ?>
    <!-- Success confirmation after POST redirect -->
    <div class="bg-green-50 rounded-xl border border-green-200 px-6 py-5">
      <div class="flex items-start gap-3">
        <svg class="w-6 h-6 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
          <h3 class="text-base font-bold text-green-900">Your feedback has been submitted. Thank you!</h3>
          <p class="text-sm text-green-700 mt-1">The technician will be notified of your rating.</p>
        </div>
      </div>
    </div>

  <?php else: ?>
    <!-- Feedback form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-6">
      <form method="POST" action="?wo_id=<?= (int)$wo['wo_id'] ?>">

        <?php if ($error): ?>
          <!-- Validation error -->
          <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-5 flex items-start gap-2">
            <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
            <p class="text-sm text-red-800 font-medium"><?= htmlspecialchars($error) ?></p>
          </div>
        <?php endif; ?>

        <!-- Star rating input -->
        <div class="mb-6">
          <label class="block text-sm font-bold text-gray-700 mb-3">
            Rating <span class="text-red-500">*</span>
          </label>
          <p class="text-xs text-gray-500 mb-3">How satisfied are you with the repair service?</p>

          <div class="star-rating-input">
            <?php for ($i = 5; $i >= 1; $i--): ?>
              <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" required />
              <label for="star<?= $i ?>" title="<?= $i ?> star<?= $i !== 1 ? 's' : '' ?>">
                <svg class="star-icon" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
              </label>
            <?php endfor; ?>
          </div>
        </div>

        <!-- Comment textarea -->
        <div class="mb-6">
          <label for="comment" class="block text-sm font-bold text-gray-700 mb-2">
            Comment <span class="text-gray-400 font-normal">(optional)</span>
          </label>
          <textarea
            name="comment"
            id="comment"
            rows="4"
            maxlength="1000"
            placeholder="Share any additional thoughts about the repair service..."
            class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-olfu-green focus:border-transparent resize-none"
          ></textarea>
          <p class="text-xs text-gray-400 mt-1.5">Maximum 1000 characters</p>
        </div>

        <!-- Submit button -->
        <button
          type="submit"
          class="w-full bg-olfu-green hover:bg-olfu-green-md text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-150 flex items-center justify-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Submit Feedback
        </button>

      </form>
    </div>
  <?php endif; ?>

</div>

<!-- Star rating CSS -->
<style>
.star-rating-input {
  display: flex;
  flex-direction: row-reverse;
  justify-content: flex-end;
  gap: 0.25rem;
}

.star-rating-input input[type="radio"] {
  display: none;
}

.star-rating-input label {
  cursor: pointer;
  transition: all 0.2s ease;
}

.star-rating-input .star-icon {
  width: 2.5rem;
  height: 2.5rem;
  color: #d1d5db; /* gray-300 */
  transition: color 0.15s ease;
}

/* Hover effect: highlight current star and all stars to the right (which are before in DOM due to reverse order) */
.star-rating-input label:hover .star-icon,
.star-rating-input label:hover ~ label .star-icon {
  color: #fbbf24; /* yellow-400 */
}

/* Selected state: highlight checked star and all stars to the right */
.star-rating-input input[type="radio"]:checked ~ label .star-icon {
  color: #fbbf24; /* yellow-400 */
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
