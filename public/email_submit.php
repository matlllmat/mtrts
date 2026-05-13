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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    .olfu-gradient { background: linear-gradient(135deg, #1a5c2a 0%, #15803d 100%); }
    .glass-effect { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
    .input-focus:focus { border-color: #1a5c2a; ring-color: rgba(26, 92, 42, 0.1); }
  </style>
</head>
<body class="bg-[#f9fafb] min-h-screen text-gray-900 selection:bg-green-100 selection:text-green-900">

<div class="fixed inset-0 -z-10 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-green-50 via-transparent to-transparent opacity-70"></div>

<div class="max-w-2xl mx-auto py-12 px-4 sm:px-6">

  <!-- Header Section -->
  <div class="flex flex-col items-center text-center mb-10">
    <div class="w-16 h-16 bg-white rounded-2xl shadow-sm border border-gray-100 flex items-center justify-center mb-4 transform transition-transform hover:scale-105 duration-300">
      <img src="<?= BASE_URL ?>public/assets/images/logo.png" alt="OLFU" class="w-12 h-12 object-contain">
    </div>
    <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight sm:text-3xl">Email Gateway</h1>
    <p class="mt-2 text-sm text-gray-500 max-w-sm">
      Send a repair request to <span class="text-olfu-green font-semibold">support@mtrts.olfu.edu.ph</span>. 
      No login required.
    </p>
  </div>

  <?php if ($flash_ok): ?>
    <div class="bg-white border border-green-100 rounded-2xl p-6 mb-8 shadow-xl shadow-green-900/5 animate-in fade-in slide-in-from-top-4 duration-500">
      <div class="flex flex-col items-center text-center">
        <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center mb-4">
          <svg class="w-6 h-6 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900">Message Received</h3>
        <p class="text-sm text-gray-600 mt-2 leading-relaxed">
          Your request has been converted into ticket <span class="font-mono font-bold text-olfu-green">#<?= htmlspecialchars($ticket_num) ?></span>. 
          Our IT team will respond via your provided email shortly.
        </p>
        <div class="mt-6 flex items-center gap-3">
          <a href="email_submit.php" class="inline-flex items-center gap-2 text-sm font-bold text-olfu-green hover:text-[#1f6e32] transition-colors">
            Send another request
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
          </a>
        </div>
      </div>
    </div>
  <?php elseif ($flash_error): ?>
    <div class="bg-red-50 border border-red-100 text-[#b91c1c] rounded-2xl p-4 mb-8 text-sm flex items-center gap-3 shadow-sm">
      <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
      <span class="font-medium">Error: <?= htmlspecialchars($flash_error) ?></span>
    </div>
  <?php endif; ?>

  <!-- Compose Form -->
  <form method="POST" action="email_submit_handler.php" enctype="multipart/form-data"
        class="bg-white rounded-3xl shadow-2xl shadow-gray-200/50 border border-gray-100 overflow-hidden ring-1 ring-gray-100">

    <!-- Header / Meta -->
    <div class="bg-gray-50/50 border-b border-gray-100 px-6 py-4 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <div class="w-2 h-2 rounded-full bg-red-400"></div>
        <div class="w-2 h-2 rounded-full bg-amber-400"></div>
        <div class="w-2 h-2 rounded-full bg-green-400"></div>
        <span class="ml-2 text-xs font-bold text-gray-400 uppercase tracking-widest">New Message</span>
      </div>
      <span class="text-[10px] font-bold text-gray-400 uppercase bg-gray-100 px-2 py-0.5 rounded">To: IT Support</span>
    </div>

    <div class="px-6 py-6 space-y-4">

      <!-- From Inputs -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 ml-1">From Name</label>
          <input type="text" name="from_name" required placeholder="John Doe"
                 class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-sm font-medium focus:outline-none focus:ring-4 focus:ring-green-500/5 focus:border-olfu-green transition-all" />
        </div>
        <div>
          <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 ml-1">Email Address</label>
          <input type="email" name="from_email" required placeholder="j.doe@olfu.edu.ph"
                 class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-sm font-medium focus:outline-none focus:ring-4 focus:ring-green-500/5 focus:border-olfu-green transition-all" />
        </div>
      </div>

      <!-- Type & Subject -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="sm:col-span-1">
          <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 ml-1">Request Type</label>
          <select name="category_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-sm font-medium focus:outline-none focus:ring-4 focus:ring-green-500/5 focus:border-olfu-green transition-all appearance-none cursor-pointer">
            <option value="">(Optional)</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int)$c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sm:col-span-2">
          <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 ml-1">Subject</label>
          <input type="text" name="subject" required placeholder="Subject of your request"
                 class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold text-gray-800 placeholder:font-medium focus:outline-none focus:ring-4 focus:ring-green-500/5 focus:border-olfu-green transition-all" />
        </div>
      </div>

      <!-- Body -->
      <div>
        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 ml-1">Description</label>
        <textarea name="body" required rows="8" placeholder="Please describe the issue in detail..."
                  class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl text-sm font-medium leading-relaxed focus:outline-none focus:ring-4 focus:ring-green-500/5 focus:border-olfu-green transition-all resize-none"></textarea>
      </div>

      <!-- Attachments Dropzone-style -->
      <div class="relative group">
        <div class="absolute inset-0 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 group-hover:border-olfu-green group-hover:bg-[#f0fdf4] transition-all duration-300"></div>
        <div class="relative px-6 py-8 flex flex-col items-center justify-center text-center pointer-events-none">
          <div class="w-10 h-10 bg-white rounded-xl shadow-sm border border-gray-100 flex items-center justify-center mb-3 text-gray-400 group-hover:text-olfu-green transition-colors">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
          </div>
          <p class="text-xs font-bold text-gray-600">Click to attach photos or files</p>
          <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-tight">JPG, PNG, PDF, MP4 (Max 10MB each)</p>
        </div>
        <input type="file" name="attachments[]" multiple accept="image/*,video/mp4,application/pdf"
               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
      </div>

    </div>

    <!-- Actions -->
    <div class="bg-gray-50/50 border-t border-gray-100 px-6 py-5 flex items-center justify-between">
      <div class="hidden sm:flex items-center gap-1 text-[10px] font-bold text-gray-400 uppercase tracking-tighter">
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        Secure Submission
      </div>
      <button type="submit"
              class="w-full sm:w-auto bg-[#1a5c2a] hover:bg-[#1f6e32] text-white text-sm font-bold px-10 py-3 rounded-2xl shadow-xl shadow-green-900/10 transition-all active:scale-95 flex items-center justify-center gap-2">
        <span>Send Message</span>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
      </button>
    </div>
  </form>

  <footer class="mt-12 text-center">
    <div class="flex items-center justify-center gap-4 mb-4">
      <span class="w-10 h-[1px] bg-gray-200"></span>
      <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Powered by MTRTS</p>
      <span class="w-10 h-[1px] bg-gray-200"></span>
    </div>
    <div class="flex items-center justify-center gap-6">
      <a href="<?= BASE_URL ?>modules/login.php" class="text-xs font-semibold text-gray-400 hover:text-olfu-green transition-colors">Staff Login</a>
      <a href="#" class="text-xs font-semibold text-gray-400 hover:text-olfu-green transition-colors">Help Center</a>
      <a href="#" class="text-xs font-semibold text-gray-400 hover:text-olfu-green transition-colors">Privacy</a>
    </div>
  </footer>
</div>

<script>
  // Simple file name display improvement (optional)
  const fileInput = document.querySelector('input[type="file"]');
  const fileText = document.querySelector('.group p.text-xs');
  
  fileInput.addEventListener('change', (e) => {
    const files = e.target.files;
    if (files.length > 0) {
      fileText.textContent = `${files.length} file(s) selected: ${Array.from(files).map(f => f.name).join(', ').substring(0, 30)}...`;
      fileText.classList.add('text-olfu-green');
    }
  });
</script>

</body>
</html>
