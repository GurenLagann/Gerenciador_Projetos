<?php

use App\Mcp\Servers\ProjectManagerServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('project-manager', ProjectManagerServer::class);
