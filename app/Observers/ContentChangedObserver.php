<?php

namespace App\Observers;

use App\Support\ContentVersion;

/** Any admin change to public content refreshes every cached page, menu and setting. */
class ContentChangedObserver
{
    public function saved(): void
    {
        ContentVersion::bump();
    }

    public function deleted(): void
    {
        ContentVersion::bump();
    }
}
