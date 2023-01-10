{{--
    Copyright © BoonWeb GmbH. All rights reserved.
    See LICENSE for license details.
 --}}
<div>
    <div class="py-1 ml-2">
        <div class="px-1 bg-green-800 text-black">SUCCESS</div>
        <em class="ml-1">
            Laravel was installed successfully. A few more manual steps are required to finish this setup:
        </em>
    </div>

    <ul>
        <li>Run `cd {{$appName}}`</li>
        <li>(option 1) Adjust your .env file to contain proper database settings and run `php artisan migrate`</li>
        <li>(option 2) Run `php artisan sail:install && sail up -d && sail artisan migrate`</li>
        <li>Run `(sail) {{$packageManager}} install`</li>
        <li>Run `(sail) {{$packageManager}} run dev`</li>
    </ul>
</div>
