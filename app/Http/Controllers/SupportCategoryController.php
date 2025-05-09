<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SupportCategory;

class SupportCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:show_support_categories'])->only('index');
        $this->middleware(['permission:edit_support_category'])->only('edit');
        $this->middleware(['permission:delete_support_category'])->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $support_categories = SupportCategory::orderBy('created_at','desc')->paginate(10);
        return view('addons.support_ticket.support_categories.index', compact('support_categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $support_category       = new SupportCategory;
        $support_category->name = $request->name;
        if ($support_category->save()) {
            flash( translate('New support category has been added successfully') )->success();
            return redirect()->route('support-categories.index');
        }
        else {
            flash( translate('Sorry! Something went wrong.') )->error();
            return back();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
         $support_category = SupportCategory::findOrFail($id);
         return view('addons.support_ticket.support_categories.edit', compact('support_category'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $support_category = SupportCategory::findOrFail($id);
        $support_category->name = $request->name;
        if ($support_category->save()) {
            flash(translate('Support Category has been updated successfully.'))->success();
            return redirect()->route('support-categories.index');
        }
        else {
            flash(translate('Sorry! Something went wrong.'))->error();
            return back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $support_category = SupportCategory::findOrFail($id);
        if(SupportCategory::destroy($id)){
            flash(translate('Category has been deleted successfully'))->success();
            return redirect()->route('support-categories.index');
        }
        return back();
    }
}
