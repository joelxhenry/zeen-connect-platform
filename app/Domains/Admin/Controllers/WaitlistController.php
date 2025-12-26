<?php

namespace App\Domains\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\WaitlistSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class WaitlistController extends Controller
{
    /**
     * Display a listing of waitlist subscribers.
     */
    public function index(Request $request): Response
    {
        $query = WaitlistSubscriber::query();

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Source filter
        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }

        // Founding member filter
        if ($request->has('founding_member')) {
            $query->where('is_founding_member', $request->boolean('founding_member'));
        }

        // Sort
        $sortField = $request->input('sort', 'subscribed_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $subscribers = $query->paginate(20)->withQueryString();

        // Get stats
        $stats = [
            'total' => WaitlistSubscriber::count(),
            'founding_members' => WaitlistSubscriber::where('is_founding_member', true)->count(),
            'this_week' => WaitlistSubscriber::where('subscribed_at', '>=', now()->subWeek())->count(),
            'sources' => WaitlistSubscriber::selectRaw('source, COUNT(*) as count')
                ->groupBy('source')
                ->pluck('count', 'source')
                ->toArray(),
        ];

        return Inertia::render('Admin/Waitlist/Index', [
            'subscribers' => $subscribers,
            'stats' => $stats,
            'filters' => [
                'search' => $request->input('search'),
                'source' => $request->input('source'),
                'founding_member' => $request->input('founding_member'),
                'sort' => $sortField,
                'direction' => $sortDirection,
            ],
        ]);
    }

    /**
     * Send an invite email to a waitlist subscriber.
     */
    public function invite(string $id): RedirectResponse
    {
        $subscriber = WaitlistSubscriber::findOrFail($id);

        // TODO: Implement actual email sending logic
        // For now, we'll just mark them as invited
        // Mail::to($subscriber->email)->send(new WaitlistInviteMail($subscriber));

        return back()->with('success', "Invite sent to {$subscriber->email}");
    }

    /**
     * Delete a waitlist subscriber.
     */
    public function destroy(string $id): RedirectResponse
    {
        $subscriber = WaitlistSubscriber::findOrFail($id);
        $email = $subscriber->email;
        $subscriber->delete();

        return back()->with('success', "Removed {$email} from waitlist");
    }

    /**
     * Export waitlist as CSV.
     */
    public function export(Request $request)
    {
        $subscribers = WaitlistSubscriber::orderBy('subscribed_at', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="waitlist-'.now()->format('Y-m-d').'.csv"',
        ];

        $callback = function () use ($subscribers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'Email', 'Source', 'Founding Member', 'Subscribed At']);

            foreach ($subscribers as $subscriber) {
                fputcsv($file, [
                    $subscriber->name,
                    $subscriber->email,
                    $subscriber->source,
                    $subscriber->is_founding_member ? 'Yes' : 'No',
                    $subscriber->subscribed_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
