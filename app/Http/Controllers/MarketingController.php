<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Models\Cart;
use Carbon\Carbon;
use Mail;
use Auth;
use Session;
use Cookie;

class MarketingController extends Controller
{
    public function list_user_and_cart_details(Request $request)
    {
        // if (Auth::user()->user_type == 'admin') {
            // Initialize the query to fetch users with carts and their details
            $query = User::whereHas('carts')->with('carts.product');
    
            // Apply date filter if provided
            if ($request->date) {
                $dates = explode(" to ", $request->date);
                $startDate = date('Y-m-d 00:00:00', strtotime($dates[0]));
                $endDate = date('Y-m-d 23:59:59', strtotime($dates[1]));
    
                $query->whereHas('carts', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                });
            }
            
            if ($request->user_name) {
                $query->where('name', 'like', '%' . $request->user_name . '%');
            }
    
            // Paginate the results
            $usersWithCartDetails = $query->paginate(15);
        // }
    
        return view('backend.user_cart_details.index', compact('usersWithCartDetails', 'request'));
    }
    
    public function sendCartReminderEmails()
    {
        \Log::info('Cart reminder email job executed at ' . now());

        // Get user with ID 112 who has items in their cart and needs a reminder email
        // $usersWithCarts = User::where('id', 112) // Add condition to target user ID 112
        //     ->whereHas('carts', function ($query) {
        //         $query->where('notify_attempt', '<', 3)
        //               ->whereNotNull('notify_date');
        //     })
        //     ->with('carts.product')
        //     ->get();
        \Log::info('Users found: ', User::whereHas('carts')->pluck('id')->toArray());
        // Get users who have items in their cart and need a reminder email
        $usersWithCarts = User::whereHas('carts', function ($query) {
            $query->where('notify_attempt', '<', 3)
                  ->whereNotNull('notify_date');
        })->with('carts.product')->get();

        $emailsSent = false;
        $usersNotified = [];
        \Log::info('Eligible users for reminder: ' . $usersWithCarts->count());
        foreach ($usersWithCarts as $user) {
            \Log::info('Checking user ID: ' . $user->id);
            // Check if user email exists
            if (!empty($user->email)) {
                $shouldSendEmail = false;
                $notifyInterval = 0;
                $cartItemsToNotify = []; // Collect cart items that need to be included in the email
        
                foreach ($user->carts as $cart) {
                    $now = Carbon::now();
        
                    // Check if it's time to send an email
                    if ($cart->notify_date <= $now) {
                        switch ($cart->notify_attempt) {
                            case 0:
                                $notifyInterval = 23; // Notify after 23 hours
                                $shouldSendEmail = true;
                                break;
                            case 1:
                                $notifyInterval = 48; // Notify after 48 hours
                                $shouldSendEmail = true;
                                break;
                            case 2:
                                $shouldSendEmail = true;
                                break;
                        }
        
                        if ($shouldSendEmail) {
                            // Collect the cart items for the email
                            $cartItemsToNotify[] = $cart;
        
                            // Update the notify attempt count and notify date for the cart
                            if ($cart->notify_attempt < 2) {
                                $cart->notify_attempt++;
                                $cart->notify_date = Carbon::now()->addHours($notifyInterval);
                            } else {
                                $cart->notify_attempt++;
                                $cart->notify_date = null; // Reset notify_date after 3 attempts
                            }
                            $cart->save();
                        }
                    }
                }
            
                // Send email if there are items to notify
                if (!empty($cartItemsToNotify)) {
                    $emailsSent = true;
                    $usersNotified[] = $user->email; // Collect user emails for the response
        
                    // Send email to the user with all cart items
                    Mail::send('emails.cart_reminder', ['user' => $user, 'cartItems' => $cartItemsToNotify], function ($message) use ($user) {
                        $message->to($user->email)
                                ->subject('You have items left in your cart');
                    });
                    // Send same email to admin
                    // $adminEmail = 'motiwalajewels786@gmail.com'; // Replace with actual admin email
                    // $adminEmail = 'webdeveloper@nexgeno.in'; // Replace with actual admin email
                    // Fetch the first admin user's email
                    $adminUser = User::where('user_type', 'admin')->first();

                    if ($adminUser && !empty($adminUser->email)) {
                        Mail::send('emails.cart_reminder', ['user' => $user, 'cartItems' => $cartItemsToNotify], function ($message) use ($adminUser, $user) {
                            $message->to($adminUser->email)
                                    ->subject('Cart Reminder Sent to ' . $user->name);
                        });
                    }


                }
            } else {
                \Log::info('User ID ' . $user->id . ' does not have a valid email address.');
            }
        }
    
        if ($emailsSent) {
            $emailsList = implode(', ', $usersNotified); // Join the emails into a single string
            return response()->json(['message' => 'Cart reminder sent to: ' . $emailsList]);
        } else {
            return response()->json(['message' => 'No users found to email']);
        }
    }

}
