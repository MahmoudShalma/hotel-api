<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class HotelController extends Controller
{
    public function index(Request $request)
    {
        // Fetch data from the provided URL
        $response = Http::get('https://api.npoint.io/dd85ed11b9d8646c5709');
        $hotels = $response->json()['hotels'];

        // Parse query parameters
        $name = $request->query('name');
        $destination = $request->query('destination');
        $price = $request->query('price');
        $dateRange = $request->query('date_range');
        $sort = $request->query('sort');

        // Filter hotels based on criteria
        $filteredHotels = collect($hotels)->filter(function ($hotel) use ($name, $destination, $price, $dateRange) {
            $matches = true;

            if ($name && isset($hotel['name'])) {
                $matches = $matches && stripos($hotel['name'], $name) !== false;
            }

            if ($destination && isset($hotel['city'])) {
                $matches = $matches && strtolower($hotel['city']) == strtolower($destination);
            }

            if ($price && isset($hotel['price'])) {
                $priceRange = explode(':', $price);
                $matches = $matches && ($hotel['price'] >= $priceRange[0] && $hotel['price'] <= $priceRange[1]);
            }

            if ($dateRange) {
                $matches = $matches && $this->isHotelAvailable($hotel, $dateRange);
            }

            return $matches;
        });

        // Sort hotels
        if ($sort) {
            $filteredHotels = $filteredHotels->sortBy($sort);
        }

        return response()->json($filteredHotels->values(), 200);
    }

    private function isHotelAvailable($hotel, $dateRange)
    {
        $availability = $hotel['availability'];

        $dateParts = explode(':', $dateRange);
        $startDate = strtotime($dateParts[0]);
        $endDate = strtotime($dateParts[1]);

        foreach ($availability as $availablePeriod) {
            $from = strtotime($availablePeriod['from']);
            $to = strtotime($availablePeriod['to']);

            if ($startDate >= $from && $endDate <= $to) {
                return true;
            }
        }

        return false;
    }
}
