<?php
// public/email_submit.php — Public-facing "Send Email" form that mimics
// emailing support@mtrts.edu.ph. Creates a ticket on submit with channel='email'.
// NO authentication required — anyone can use this, just like real email.

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../modules/tickets/functions.php';

$categories = get_all_categories($pdo);

$flash_error = $_GET['err'] ?? '';
$flash_ok    = $_GET['ok']  ?? '';
$ticket_num  = $_GET['tn']  ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MTRTS — Email Gateway</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="<?= BASE_URL ?>public/assets/images/logo.png">
  <style>
    body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
  </style>
</head>
<body class="bg-gray-100 min-h-screen">

<div class="max-w-2xl mx-auto py-8 px-4">

  <!-- Header strip -->
  <div class="flex items-center gap-3 mb-6">
    <img src="<?= BASE_URL ?>public/assets/images/logo.png" alt="OLFU" class="w-10 h-10 object-contain">
    <div>
      <h1 class="text-lg font-bold text-gray-900 leading-tight">MTRTS Email Gateway</h1>
      <p class="text-xs text-gray-500 leading-tight">Send a repair request — like emailing <span class="font-mono">support@mtrts.olfu.edu.ph</span></p>
    </div>
  </div>

  <?php if ($flash_ok): ?>
    <div class="bg-green-50 border border-green-200 text-green-900 rounded-xl p-5 mb-4 shadow-sm">
      <div class="flex items-start gap-3">
        <svg class="w-6 h-6 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
        </svg>
        <div class="flex-1">
          <p class="font-semibold">Email received — ticket created.</p>
          <p class="text-sm mt-1">Your ticket number is <span class="font-mono font-bold"><?= htmlspecialchars($ticket_num) ?></span>. IT will follow up shortly.</p>
          <a href="email_submit.php" class="inline-block mt-3 text-sm text-green-700 hover:underline font-semibold">Send another →</a>
        </div>
      </div>
    </div>
  <?php elseif ($flash_error): ?>
    <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 mb-4 text-sm shadow-sm">
      <strong>Error:</strong> <?= htmlspecialchars($flash_error) ?>
    </div>
  <?php endif; ?>

  <!-- Compose card -->
  <form method="POST" action="email_submit_handler.php" enctype="multipart/form-data"
        class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

    <!-- Compose header -->
    <div class="bg-gray-50 border-b border-gray-200 px-5 py-3 flex items-center gap-2">
      <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6m-18 0v8a2 2 0 002 2h14a2 2 0 002-2V8m-18 0V6a2 2 0 012-2h14a2 2 0 012 2v2"/>
      </svg>
      <span class="text-sm font-semibold text-gray-700">New Message</span>
      <span class="ml-auto text-xs text-gray-400">To: support@mtrts.olfu.edu.ph</span>
    </div>

    <div class="px-5 py-4 space-y-3">

      <!-- From Name -->
      <div class="flex items-center gap-3 border-b border-gray-100 pb-3">
        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">From</label>
        <input type="text" name="from_name" required placeholder="Your full name"
               class="flex-1 text-sm border-0 focus:outline-none focus:ring-0 px-0" />
      </div>

      <!-- From Email -->
      <div class="flex items-center gap-3 border-b border-gray-100 pb-3">
        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">Email</label>
        <input type="email" name="from_email" required placeholder="you@olfu.edu.ph"
               class="flex-1 text-sm border-0 focus:outline-none focus:ring-0 px-0" />
      </div>

      <!-- Category (optional) -->
      <div class="flex items-center gap-3 border-b border-gray-100 pb-3">
        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">Type</label>
        <select name="category_id" class="flex-1 text-sm border-0 focus:outline-none focus:ring-0 px-0 bg-white">
          <option value="">— Optional: pick a category —</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Subject -->
      <div class="flex items-center gap-3 border-b border-gray-100 pb-3">
        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">Subject</label>
        <input type="text" name="subject" required placeholder="What's broken? (e.g., Projector won't power on)"
               class="flex-1 text-sm border-0 focus:outline-none focus:ring-0 px-0 font-medium" />
      </div>

      <!-- Body -->
      <div>
        <textarea name="body" required rows="10" placeholder="Describe the issue in detail — what happened, when, what you've tried, room number, etc."
                  class="w-full text-sm border-0 focus:outline-none focus:ring-0 px-0 resize-y"></textarea>
      </div>

      <!-- Attachments -->
      <div class="border-t border-gray-100 pt-3">
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Attach files (optional)</label>
        <input type="file" name="attachments[]" multiple accept="image/*,video/mp4,application/pdf"
               class="text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-olfu-green/10 file:text-olfu-green hover:file:bg-olfu-green/20" />
        <p class="text-[11px] text-gray-400 mt-1">JPG, PNG, WEBP, MP4, PDF · max 10 MB each · up to 5 files</p>
      </div>

    </div>

    <!-- Footer / Send -->
    <div class="bg-gray-50 border-t border-gray-200 px-5 py-3 flex items-center justify-end gap-3">
      <button type="submit"
              class="bg-olfu-green hover:bg-olfu-green-md bg-[#1a5c2a] hover:bg-[#1f6e32] text-white text-sm font-semibold px-5 py-2 rounded-lg shadow-sm transition-colors flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
        Send
      </button>
    </div>
  </form>

  <p class="text-center text-xs text-gray-400 mt-6">
    MTRTS · Media Technology Repair Tracker System ·
    <a href="<?= BASE_URL ?>modules/login.php" class="text-gray-500 hover:underline">Sign in</a>
  </p>
</div>

</body>
</html>
