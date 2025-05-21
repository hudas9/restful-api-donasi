<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Category;
use App\Policies\CategoryPolicy;
use App\Models\Program;
use App\Policies\ProgramPolicy;
use App\Models\Report;
use App\Policies\ReportPolicy;
use App\Models\ReportComment;
use App\Policies\ReportCommentPolicy;
use App\Models\ProgramComment;
use App\Policies\ProgramCommentPolicy;
use App\Models\Donation;
use App\Policies\DonationPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Category::class => CategoryPolicy::class,
        Program::class => ProgramPolicy::class,
        Report::class => ReportPolicy::class,
        ProgramComment::class => ProgramCommentPolicy::class,
        ReportComment::class => ReportCommentPolicy::class,
        Donation::class => DonationPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
