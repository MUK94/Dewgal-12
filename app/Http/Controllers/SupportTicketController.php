<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SupportTicket;
use App\Models\SupportCategory;
use App\Models\SupportTicketReply;
use App\Models\SystemConfiguration;
use App\Models\Staff;
use App\User;
use Auth;
use Gate;

class SupportTicketController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:show_active_tickets'])->only('active_ticket');
        $this->middleware(['permission:assign_ticket_to_agent'])->only('edit');
        $this->middleware(['permission:show_my_tickets'])->only('my_ticket');
        $this->middleware(['permission:show_solved_tickets'])->only('solved_ticket');
        $this->middleware(['permission:delete_solved_tickets'])->only('destroy');
        $this->middleware(['permission:default_ticket_assigned_agent'])->only('default_ticket_assigned_agent');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function active_ticket()
    {
        $support_tickets = SupportTicket::where('status', '0')->orderBy('created_at', 'desc')->paginate(10);
        return view('addons.support_ticket.support_tickets.active_tickets', compact('support_tickets'));
    }

    public function user_index()
    {
        $support_tickets = SupportTicket::where('sender_user_id', Auth::user()->id)->orderBy('created_at', 'desc')->paginate(8);
        return view('addons.support_ticket.frontend.support_ticket_history', compact('support_tickets'));
    }

    // deafult staff for assigning ticket
    public function default_ticket_assigned_agent ()
    {
        $staffs = User::where('user_type','admin')->orWhere('user_type','staff')->get();
        return view('addons.support_ticket.default_ticket_assigned_agent', compact('staffs'));
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

    public function user_ticket_create()
    {
        $support_categories = SupportCategory::orderBy('created_at','desc')->get();
        return view('addons.support_ticket.frontend.support_ticket_create', compact('support_categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $default_agent = get_setting('default_ticket_assigned_user');
        $support_ticket = new SupportTicket;
        $support_ticket->subject = $request->subject;
        $support_ticket->support_category_id = $request->support_category_id;
        $support_ticket->sender_user_id = Auth::user()->id;
        if ($default_agent != null) {
            $support_ticket->assigned_user_id = $default_agent;
        }
        $support_ticket->ticket_id = date('Ymd-his');
        $support_ticket->description = $request->description;
        $support_ticket->attachments = $request->attachments;
        if ($support_ticket->save()) {
            $submit_id = $support_ticket->ticket_id;
            flash(translate('Support Ticket has been sent successfully'))->success();
            return view('addons.support_ticket.frontend.support_ticket_create_success', compact('submit_id'));
        }
        else {
            flash(translate('Sorry! Something went wrong.'))->error();
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
        $support_ticket = SupportTicket::findOrFail(decrypt($id));
        $support_ticket->seen = '1';
        $support_ticket->save();
        $support_replies    = SupportTicketReply::where('support_ticket_id', $support_ticket->id)->get();
        foreach ($support_replies as $support_replie) {
            if($support_replie->replied_user_id != Auth::user()->id){
                $support_replie->seen = 1;
                $support_replie->save();
            }
        }
        return view('addons.support_ticket.support_tickets.show', compact('support_ticket'));
    }

    public function user_view_details($id)
    {
        $support_ticket     = SupportTicket::findOrFail(decrypt($id));
        $support_replies    = SupportTicketReply::where('support_ticket_id', $support_ticket->id)->orderBy('created_at', 'desc')->get();
        foreach ($support_replies as $support_replie) {
            if($support_replie->replied_user_id != Auth::user()->id){
                $support_replie->seen = 1;
                $support_replie->save();
            }
         }
        return view('addons.support_ticket.frontend.support_ticket_show', compact('support_replies', 'support_ticket'));
    }

    public function my_ticket()
    {
        $my_tickets = SupportTicket::where('assigned_user_id', Auth::user()->id)->where('status', '0')->orderBy('created_at', 'desc')->paginate(10);
        if (count($my_tickets) == 0) {
            return view('addons.support_ticket.support_tickets.no_ticket');
        }
        else {
            return view('addons.support_ticket.support_tickets.my_tickets', compact('my_tickets'));
        }
    }

    public function solved_ticket(Request $request)
    {
        $col_name = null;
        $query = null;
        $priority = null;
        $sort_search = null;
        $support_tickets = SupportTicket::where('status', '1');
        if ($request->search != null || $request->type != null || $request->priority != null) {
            if ($request->search != null){
                $support_tickets = $support_tickets->where('ticket_id', 'like', '%'.$request->search.'%');
                $sort_search = $request->search;
            }
            if ($request->type != null){
                $var = explode(",", $request->type);
                $col_name = $var[0];
                $query = $var[1];
                $support_tickets = $support_tickets->orderBy($col_name, $query);
            }
            if ($request->priority != null){
                $priority = $request->priority;
                $support_tickets = $support_tickets->where('priority', $priority);
            }
            $support_tickets = $support_tickets->paginate(12);
        }
        else {
            $support_tickets = $support_tickets->orderBy('created_at', 'desc')->paginate(10);
        }

        return view('addons.support_ticket.support_tickets.solved_ticket', compact('support_tickets', 'col_name', 'query', 'sort_search', 'priority'));
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $support_ticket = SupportTicket::findOrFail(decrypt($id));
        $staffs = User::where('user_type','admin')->orWhere('user_type','staff')->latest()->get();
        return view('addons.support_ticket.support_tickets.edit', compact('staffs','support_ticket'));
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
        $support_ticket = SupportTicket::findOrFail($id);
        $support_ticket->priority = $request->priority;
        $support_ticket->assigned_user_id = $request->assigned_user_id;
        $support_ticket->assigned_user_seen = '0';
        if ($support_ticket->save()) {
            flash('Support Ticket has been assigned successfully')->success();
            return redirect()->route('support-tickets.active_ticket');
        }
        else {
            flash('Sorry! Something went wrong.')->error();
            return back();
        }
    }

    public function ticket_reply(Request $request)
    {
        $support_ticket = SupportTicket::findOrFail($request->support_ticket_id);

        $ticket_reply                     = new SupportTicketReply;
        $ticket_reply->support_ticket_id  = $request->support_ticket_id;
        $ticket_reply->replied_user_id    = Auth::user()->id;
        $ticket_reply->reply              = $request->reply;
        $ticket_reply->attachments        = $request->attachments;
        if ($ticket_reply->save()) {
            if (Auth::user()->user_type == 'admin' || Auth::user()->user_type == 'staff') {
                $support_ticket->status   = $request->status;
                $support_ticket->save();
                flash('Reply has been sent successfully')->success();
                return redirect()->route('support-tickets.my_ticket');
            }
            else {
                $support_ticket->status = "0";
                $support_ticket->save();
                flash('Reply has been sent successfully')->success();
                return redirect()->route('support-tickets.user_index');
            }
        }
        else {
            flash('Sorry! Something went wrong.')->error();
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
        $support_ticket = SupportTicket::findOrFail($id);
        foreach ($support_ticket->supportTicketReplies as $key => $support_ticket_reply) {
            $support_ticket_reply->delete();
        }
        if(SupportTicket::destroy($id)){
            flash(translate('Ticket has been deleted successfully'))->success();
            return redirect()->route('support-tickets.solved_ticket');
        }
        else{
            flash(translate('Something went wrong'))->error();
            return back();
        }
    }
}
