<?php

namespace App\Http\Controllers\API;

use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Intervention\Image\Facades\Image;
use Validator;

class InvoiceTemplatesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
    //* @return \Illuminate\Http\Response
     */
    public function index()
    {
        $invoiceTemplates = InvoiceTemplate::all();

        if ($invoiceTemplates) {
            return response()->json($invoiceTemplates, 200);
        } else {
            return response()->json([
                'message' => 'Expectation Failed',
                'status' => 417
            ], 417);
        }

    }

    public function showExpense () {
        $invoiceTemplates = InvoiceTemplate::where('type', InvoiceTemplate::TYPE_EXPENSE)->get();

        if ($invoiceTemplates) {
            return response()->json($invoiceTemplates, 200);
        } else {
            return response()->json([
                'message' => 'Expectation Failed',
                'status' => 417
            ], 417);
        }
    }

    public function showRevenue () {
        $invoiceTemplates = InvoiceTemplate::where('type', InvoiceTemplate::TYPE_REVENUE)->get();

        if ($invoiceTemplates) {
            return response()->json($invoiceTemplates, 200);
        } else {
            return response()->json([
                'message' => 'Expectation Failed',
                'status' => 417
            ], 417);
        }
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
     * @param  \App\Http\Requests\UserGroupsRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'type' => 'required|integer',
            'name' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
                'status' => 417
            ], 417);
        }


        $result = InvoiceTemplate::create([
            'content' => $request->input('content'),
            'type' => $request->input('type'),
            'name' => $request->input('name')
        ]);

        if ($result) {
            return response()->json([
                'message' => 'Added',
                'id' => $result->id,
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                'message' => 'Expectation Failed',
                'status' => 417
            ], 417);
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
        $invoiceTemplate = InvoiceTemplate::findOrFail($id);

        if ($invoiceTemplate) {
            return response()->json($invoiceTemplate, 200);
        } else {
            return response()->json([
                'message' => 'Expectation Failed',
                'status' => 417
            ], 417);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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

        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'type' => 'required|integer',
            'name' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
                'status' => 417
            ], 417);
        }

        $invoiceTemplate = InvoiceTemplate::findOrFail($id);

        if ($invoiceTemplate) {
            $invoiceTemplate->content = $request->input('content');
            $invoiceTemplate->type = $request->input('type');
            $invoiceTemplate->name = $request->input('name');
            $invoiceTemplate->save();

            return response()->json([
                'message' => 'Updated',
                'id' => $invoiceTemplate->id,
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                'message' => 'Expectation Failed',
                'status' => 417
            ], 417);
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

        if (InvoiceTemplate::destroy($id)) {
            return response()->json([
                'message' => 'Deleted',
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                'message' => 'Expectation Failed',
                'status' => 417
            ], 417);
        }

    }

    public function addFile (Request $request) {

        $validator = Validator::make($request->all(), [
            'files' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
                'status' => 417
            ], 417);
        }

        $filename = Carbon::now()->format('Y-m-d')."-".strtotime(Carbon::now()).rand(0,9999999).".".$request->file('files')[0]->getClientOriginalExtension();
        $request->file('files')[0]->storeAs('public/documents', $filename);
        $result_crop = $request->file('files')[0]->storeAs('public/documents/thumbnail', $filename);


        $th = Image::make(public_path('storage/documents/thumbnail/'.$filename))->resize(500, 500, function ($constraint) {
            $constraint->aspectRatio();
        });
        $th->save(public_path('storage/documents/thumbnail/'.$filename));


        if ($result_crop) {
            return response()->json([
                'message' => 'Added',
                'url' => url('/').'/storage/documents/thumbnail/'.$filename,
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                'message' => 'Expectation Failed',
                'status' => 417
            ], 417);
        }
    }

    public function showPDF ($invoice_template_id, $invoice_id) {

        // select invoice template
        $template = InvoiceTemplate::where('id', $invoice_template_id)->first();

        // select invoice data
        $invoice = Invoice::where('id', $invoice_id)->with('incomeCompany', 'expenseCompany', 'incomeCustomer', 'participants', 'participants.employee')->first();

        $participantsName = '';
        $participantsCount = count($invoice->participants);
        $i_participants = 0;

        $table = '';

        if ($invoice->participants) {

            $table .= '<table style="width: 100%;font-family: \'CenturyGothic\', CenturyGothic, AppleGothic, sans-serif;"><tbody><tr> <td style="background-color: rgb(65, 174, 167); color: rgb(255, 255, 255); width: 20%;">Participants</td> <td style="background-color: rgb(65, 174, 167); color: rgb(255, 255, 255); width: 30%">Description</td> <td style="background-color: rgb(65, 174, 167); color: rgb(255, 255, 255); width: 15%;">Unit</td> <td style="background-color: rgb(65, 174, 167); color: rgb(255, 255, 255); white-space: normal; width: 10%">Unit Price</td> <td style="background-color: rgb(65, 174, 167); color: rgb(255, 255, 255); width: 10%;">Quantity</td> <td style="background-color: rgb(65, 174, 167); color: rgb(255, 255, 255); width: 10%;">Total</td></tr>';

            foreach ($invoice->participants as $participant) {
                if ($i_participants+1 == $participantsCount) {
                    $participantsName .= $participant->name;
                } else {
                    $participantsName .= $participant->name.', ';
                }

                $table .= '<tr><td style="word-break: break-all;word-wrap: break-word;">'.( ($participant->employee) ? $participant->employee->name.' '.$participant->employee->last_name : '' ).'</td> <td style="word-break: break-all;word-wrap: break-word;">'.($participant->description ? htmlentities($participant->description, ENT_QUOTES) : "").'</td> <td style="word-break: break-all;word-wrap: break-word;">'.($participant->unit ?? "").'</td> <td style="word-break: break-all;word-wrap: break-word;">'.($participant->unit_price ?? "").'</td> <td style="word-break: break-all;word-wrap: break-word;">'.($participant->quantity ?? "").'</td><td style="word-break: break-all;word-wrap: break-word;">'.($participant->total ?? "").'</td></tr>';
                $i_participants++;
            }

            $table .= '</tbody></table>';
        } else {
            $table = '<table style="width: 100%;font-family: \'CenturyGothic\', CenturyGothic, AppleGothic, sans-serif;"><tbody><tr> <td style="background-color: rgb(65, 174, 167); color: rgb(255, 255, 255); width: 20%;">Participants</td> <td style="background-color: rgb(65, 174, 167); color: rgb(255, 255, 255); width: 45%">Description</td> <td style="background-color: rgb(65, 174, 167); color: rgb(255, 255, 255); width: 15%;">Unit</td> <td style="background-color: rgb(65, 174, 167); color: rgb(255, 255, 255); white-space: normal; width: 5%">Unit Price</td> <td style="background-color: rgb(65, 174, 167); color: rgb(255, 255, 255); width: 5%;">Quantity</td> <td style="background-color: rgb(65, 174, 167); color: rgb(255, 255, 255); width: 5%;">Total</td></tr></tbody></table>';
        }

        $content = $template->content;


        if ($invoice->type == Invoice::TYPE_REVENUE) {
            $listOfValues = [
                '{TOI}' => 'Revenue',
                '{IN}' => $invoice->invoice_number,
                '{NI}' => $invoice->net_total,
                '{IC}' => $invoice->incomeCompany->name ?? '',
                '{TWC}' => $invoice->incomeCustomer->name ?? '',
                '{ID}' => $invoice->invoice_date,
                '{IA}' => $invoice->amount,
                '{P}' => $invoice->place,
                '{T}' => $invoice->total,
                '{SBP}' => $invoice->should_be_paid_by,
                '{MwSt}' => $invoice->tax,
                '{Discount}' => $invoice->discount,
                '{TOIC}' => $invoice->cost_type == Invoice::COST_TYPE_BILLING ? 'Billing' : 'Compensation' ,
                '{CA}' => $invoice->incomeCustomer->address ?? '',
                '{Table}' => $table
            ];
        } else {
            $listOfValues = [
                '{TOI}' => 'Cost',
                '{IN}' => $invoice->invoice_number,
                '{NI}' => $invoice->net_total,
                '{IC}' => $invoice->expense_issuing ?? '',
                '{TWC}' => $invoice->expenseCompany->name ?? '',
                '{ID}' => $invoice->invoice_date,
                '{IA}' => $invoice->amount,
                '{P}' => $invoice->place,
                '{T}' => $invoice->total,
                '{MwSt}' => $invoice->tax,
                '{Discount}' => $invoice->discount,
                '{TOIC}' => $invoice->cost_type == Invoice::COST_TYPE_BILLING ? 'Billing' : 'Compensation' ,
                '{CA}' => $invoice->incomeCustomer->address ?? '',
                '{Table}' => $table
            ];
        }

        // replace all matches
        foreach ($listOfValues as $k=>$v) {
            $content = str_replace($k, $v, $content);
        }


        $text = '<style>
table { border: none; border-collapse: collapse; table-layout:fixed; width: 100% !important;word-break: break-all;word-wrap: break-word;}
table td { border: 1px solid #000; }
img {width:100%; display: block;}
@font-face {
        font-family: "Century Gothic";
        font-weight: normal;
        font-style: normal;
        font-variant: normal;
        src: url('.storage_path('fonts/CenturyGothic/CenturyGothic.ttf').') format("truetype");
      }
      @font-face {
        font-family: "CenturyGothic";
        font-weight: normal;
        font-style: normal;
        font-variant: normal;
        src: url('.storage_path('fonts/CenturyGothic/CenturyGothic.ttf').') format("truetype");
      }
      
      @font-face {
        font-family: "Impact";
        font-weight: normal;
        font-style: normal;
        font-variant: normal;
        src: url('.storage_path('fonts/Impact/Impact.ttf').') format("truetype");
      }
      
      @font-face {
        font-family: "Verdana";
        font-weight: normal;
        font-style: normal;
        font-variant: normal;
        src: url('.storage_path('fonts/Verdana/Verdana.ttf').') format("truetype");
      }
</style>';


        $filename = Carbon::now()->format('Y-m-d')."-".strtotime(Carbon::now()).rand(0,9999999).".pdf";

        $content = preg_replace_callback(
            "/(<img[^>]*src *= *[\"']?)([^\"']*)/i",
            function ($matches) {
                $img = file_get_contents($matches['2']);
                $data = base64_encode($img);
                return '<img src="data:image/png;base64,'.$data;
            },
            $content
        );

        // generate pdf
        $pdf = SnappyPdf::loadHTML($text.$content)->setWarnings(false);
        $pdf->setWarnings(false)->save(public_path('storage/documents/'.$filename));

        return [
            'link' => url('/').'/storage/documents/'.$filename
        ];
    }
    
}