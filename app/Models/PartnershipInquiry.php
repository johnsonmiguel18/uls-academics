<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A message from the Global Engagement site's "Let's connect" form. */
class PartnershipInquiry extends Model
{
    public const STATUSES = ['new' => 'New', 'in_progress' => 'In progress', 'answered' => 'Answered', 'closed' => 'Closed'];

    protected $fillable = ['name', 'email', 'organization', 'interest', 'message', 'status', 'notes'];
}
