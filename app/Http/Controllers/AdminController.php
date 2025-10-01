<?php

namespace App\Http\Controllers;

use App\Models\SellerVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    /**
     * Get all seller verification applications
     */
    public function getSellerApplications(Request $request)
    {
        $status = $request->query('status', 'all'); // all, pending, approved, rejected
        
        $query = SellerVerification::with('seller');
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        $applications = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $applications
        ]);
    }

    /**
     * Get single seller verification details
     */
    public function getSellerApplication($id)
    {
        $application = SellerVerification::with('seller', 'reviewer')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $application
        ]);
    }

    /**
     * Approve seller application
     */
    public function approveSeller(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:500',
        ]);

        $verification = SellerVerification::findOrFail($id);
        
        $verification->status = 'approved';
        $verification->reviewed_by = $request->user()->id; // Assumes admin is authenticated
        $verification->reviewed_at = now();
        $verification->remarks = $request->remarks;
        $verification->save();

        // Update user role to confirmed seller
        $verification->seller->update(['role' => 'verified_seller']);

        return response()->json([
            'success' => true,
            'message' => 'Seller application approved successfully',
            'data' => $verification
        ]);
    }

    /**
     * Reject seller application
     */
    public function rejectSeller(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'required|string|max:500',
        ]);

        $verification = SellerVerification::findOrFail($id);
        
        $verification->status = 'rejected';
        $verification->reviewed_by = $request->user()->id;
        $verification->reviewed_at = now();
        $verification->remarks = $request->remarks;
        $verification->save();

        return response()->json([
            'success' => true,
            'message' => 'Seller application rejected',
            'data' => $verification
        ]);
    }

    /**
     * Get statistics for dashboard
     */
    public function getStatistics()
    {
        $stats = [
            'pending_sellers' => SellerVerification::where('status', 'pending')->count(),
            'approved_sellers' => SellerVerification::where('status', 'approved')->count(),
            'rejected_sellers' => SellerVerification::where('status', 'rejected')->count(),
            'total_sellers' => SellerVerification::count(),
            'approved_today' => SellerVerification::where('status', 'approved')
                ->whereDate('reviewed_at', today())
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Download/view uploaded document
     */
    public function viewDocument($id, $documentType)
    {
        $verification = SellerVerification::findOrFail($id);
        
        $allowedTypes = [
            'gov_id', 'selfie_with_id', 'proof_of_address',
            'dti_sec', 'mayors_permit', 'bir_certificate',
            'fda_certificate', 'product_labels'
        ];
        
        if (!in_array($documentType, $allowedTypes)) {
            return response()->json(['error' => 'Invalid document type'], 400);
        }
        
        $filePath = $verification->$documentType;
        
        if (!$filePath || !file_exists(public_path($filePath))) {
            return response()->json(['error' => 'Document not found'], 404);
        }
        
        return response()->file(public_path($filePath));
    }
}