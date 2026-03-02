<?php

namespace Webkul\Mcp\Models;

use Webkul\Security\Models\User as SecurityUser;

class McpOAuthUser extends SecurityUser
{
    protected $table = 'users';
}
