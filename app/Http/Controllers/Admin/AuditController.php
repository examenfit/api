<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AuditController extends Controller
{
    public function index()
    {
        return Audit::with('user', 'auditable')->orderBy('created_at', 'DESC')->paginate(20);
    }
}
