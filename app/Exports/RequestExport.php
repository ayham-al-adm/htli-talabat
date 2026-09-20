<?php

namespace App\Exports;

use App\Models\Request;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;

class RequestExport implements FromView, ShouldAutoSize
{
    use Exportable;

    protected $requests;
    protected $totals;

    /**
     *
     */
    public function __construct($requests, $totals = null) {
        $this->requests = $requests;
        $this->totals = $totals;
    }

    public function view(): View
    {
        return view('requests.requests', [
            'results' => $this->requests,
            'totals' => $this->totals
        ]);
    }
}
