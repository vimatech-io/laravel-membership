<?php

declare(strict_types=1);

namespace Vimatech\Membership\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Vimatech\Membership\Concerns\HasMemberships;

class User extends Model
{
    use HasMemberships;

    protected $guarded = [];

    protected $table = 'users';
}
