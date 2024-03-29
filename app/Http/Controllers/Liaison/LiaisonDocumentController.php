<?php

namespace App\Http\Controllers\Liaison;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Models\Document;
use App\Models\DocumentRoute;
use App\Models\DocumentRouteDetail;
use App\Models\DocumentTrack;

use App\Models\DocumentLog;

class LiaisonDocumentController extends Controller
{
    //

    public function __construct(){
        $this->middleware('auth');
    }


    public function index(){
        return view('liason.liason-documents');
    }

    public function getDocumentRoutes(){
        return DocumentRoute::with('route_details')
            ->get();
    }

    public function getDocuments(Request $req){
        $sort = explode('.', $req->sort_by);

        $user = Auth::user();

        $data = Document::with(['route', 'document_tracks', 'document_logs'])
            ->where('tracking_no', 'like', $req->document . '%')
            ->where('user_id', $user->user_id)
            ->orderBy($sort[0], $sort[1])
            ->paginate($req->perpage);

        return $data;
    }


    public function store(Request $req){
        //return $req;

        $req->validate([
            'document_name' => ['required', 'unique:documents'],
            'route_id' => ['required']
        ], $message = [
            'route_id.required' => 'Select document route.'
        ]);

        $trakcing_no = strtoupper(substr(md5(time() . $req->document_name), -7));
        $user = Auth::user();

        $routeDetails = DocumentRouteDetail::where('route_id', $req->route_id)
            ->orderBy('order_no', 'asc')
            ->get();

        $data = Document::create([
            'user_id' => $user->user_id,
            'tracking_no' => $trakcing_no,
            'document_name' => $req->document_name,
            'route_id' => $req->route_id,
            'fowarded_datetime' => date('Y-m-d H:i'),
        ]);

        foreach($routeDetails as $detail){
            $docTrack = DocumentTrack::create([
                'document_id' => $data->document_id,
                'route_id' => $req->route_id,
                'route_detail_id' => $detail['route_detail_id'],
                'office_id' => $detail['office_id'],
                'order_no' => $detail['order_no'],
                'is_origin' => $detail['is_origin'],
                'is_last' => $detail['is_last']
            ]);
        }

        DocumentLog::create([
            'tracking_no' => $trakcing_no,
            'action' => 'CREATED',
            'action_datetime' => date('Y-m-d H:i'),
            'sys_user' => $user->lname . ', ' . $user->fname,
            'office' => 'ORIGIN'
        ]);

        return response()->json([
            'status' => 'saved'
        ], 200);

    }

    public function forwardDoc($id){
        
        $data = Document::find($id);
        $data->is_forwarded = 1;
        $data->save();

        //get the next step
        //get the next row/track
        $nextData = DocumentTrack::where('document_id', $id)
            ->where('is_forwarded', 0)
            ->orderBy('order_no', 'asc')
            ->limit(1)
            ->first();

        // DocumentTrack::where('document_id', $id)
        //     ->where('is_origin', 1)
        //     ->update([
        //         'is_forwarded' => 1, 
        //         'datetime_forwarded' => date('Y-m-d H:i:s')
        //     ]);

        $nxt = DocumentTrack::with('office')->find($nextData->document_track_id);
        $nxt->is_forward_from = 1;
        $nxt->save();
        
        
        $user = Auth::user();
        
        DocumentLog::create([
            'tracking_no' => $data->tracking_no,
            'action' => 'FORWARDED',
            'action_datetime' => date('Y-m-d H:i'),
            'sys_user' => $user->lname . ', ' . $user->fname,
            'office' => $nxt->office->office
        ]);
 
        return response()->json([
            'status' => 'forwarded'
        ], 200);
    }


    public function destroy($id){
        Document::destroy($id);

        return response()->json([
            'status' => 'deleted'
        ], 200);
    }
}
