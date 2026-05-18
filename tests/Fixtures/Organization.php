<?php

declare(strict_types=1);

namespace Vimatech\Membership\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Vimatech\Membership\Concerns\HasMembers;

class Organization extends Model
{
    use HasMembers;

    protected $guarded = [];

    protected $table = 'organizations';
}
