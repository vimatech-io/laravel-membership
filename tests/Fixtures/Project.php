<?php

declare(strict_types=1);

namespace Vimatech\Membership\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Vimatech\Membership\Concerns\HasMembers;

class Project extends Model
{
    use HasMembers;

    protected $guarded = [];

    protected $table = 'projects';
}
