<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OutgoingController extends Controller
{
    public function dailyPlanning()
    {
        return view('outgoing.daily_planning');
    }

    public function customerPo()
    {
        return view('outgoing.customer_po');
    }

    public function productMapping()
    {
        return view('outgoing.product_mapping');
    }

    public function deliveryRequirements()
    {
        return view('outgoing.delivery_requirements');
    }

    public function gciInventory()
    {
        return view('outgoing.gci_inventory');
    }

    public function stockAtCustomers()
    {
        return view('outgoing.stock_at_customers');
    }

    public function deliveryPlan()
    {
        return view('outgoing.delivery_plan');
    }
}

