<div class="mx-auto my-auto grid w-full max-w-4xl overflow-hidden rounded-xl md:grid-cols-2">
    <aside class="flex items-center justify-center bg-indigo-50 p-6 md:p-10">
        <img
            src="{{ asset('images/artel-fatias.png') }}"
            alt=""
            width="800"
            height="880"
            class="h-36 w-auto md:h-auto md:w-full md:max-w-xs"
        >
    </aside>

    <section class="flex items-center bg-white p-8 md:p-12">
        <div class="mx-auto w-full max-w-sm">
            {{ $slot }}
        </div>
    </section>
</div>
