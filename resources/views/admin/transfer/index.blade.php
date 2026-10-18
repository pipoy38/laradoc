<x-layouts.admin title="Transfer">
<div class="grid max-w-4xl gap-4 md:grid-cols-2">
  <section class="card p-4">
    <h2 class="mb-2 font-semibold">Export</h2>
    <p class="text-sm">Download all categories, articles (with status, tags and SEO fields) and the images they use as one zip.</p>
    <p class="hint mt-2">Left out: users, settings, redirects and revision history.</p>
    <form method="post" action="{{ admin_route('transfer.export') }}" class="mt-4">@csrf<button class="btn btn-primary">Download export</button></form>
  </section>
  <section class="card p-4">
    <h2 class="mb-2 font-semibold">Import</h2>
    <form method="post" action="{{ admin_route('transfer.import') }}" enctype="multipart/form-data">@csrf
      <div class="field"><label for="file">Export file</label><input id="file" type="file" name="file" accept=".zip,application/zip" required class="text-sm"></div>
      <p class="hint mb-3">Upload a zip made by Export on another Laradoc site. Existing articles with the same address are updated; nothing is deleted.</p>
      <button class="btn btn-primary">Import</button>
    </form>
  </section>
</div>
</x-layouts.admin>
