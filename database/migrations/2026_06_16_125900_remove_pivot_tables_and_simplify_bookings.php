<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('services', 'saloon_id')) {
            Schema::table('services', function (Blueprint $table) {
                $table->foreignId('saloon_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('services', 'price')) {
            Schema::table('services', function (Blueprint $table) {
                $table->decimal('price', 10, 2)->nullable()->after('description');
            });
        }

        if (! Schema::hasColumn('services', 'duration')) {
            Schema::table('services', function (Blueprint $table) {
                $table->integer('duration')->nullable()->after('price');
            });
        }

        if (! Schema::hasColumn('bookings', 'service_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->foreignId('service_id')->nullable()->after('saloon_id')->constrained()->nullOnDelete();
            });
        }

        $this->copySaloonServicePivotsToServices();
        $this->copyBookingItemsToBookings();

        if (Schema::hasTable('booking_items')) {
            Schema::dropIfExists('booking_items');
        }

        if (Schema::hasTable('bridal_packages_services')) {
            Schema::dropIfExists('bridal_packages_services');
        }

        if (Schema::hasTable('salon_services')) {
            Schema::dropIfExists('salon_services');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('booking_items')) {
            Schema::create('booking_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained()->onDelete('cascade');
                $table->foreignId('service_id')->constrained()->onDelete('cascade');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bridal_packages_services')) {
            Schema::create('bridal_packages_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('package_id')->constrained('bridal_packages')->onDelete('cascade');
                $table->foreignId('service_id')->constrained()->onDelete('cascade');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('salon_services')) {
            Schema::create('salon_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('saloon_id')->constrained()->onDelete('cascade');
                $table->foreignId('service_id')->constrained()->onDelete('cascade');
                $table->decimal('price', 10, 2);
                $table->integer('duration');
                $table->timestamps();
            });
        }

        if (Schema::hasColumn('bookings', 'service_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropConstrainedForeignId('service_id');
            });
        }

        if (Schema::hasColumn('services', 'duration')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('duration');
            });
        }

        if (Schema::hasColumn('services', 'price')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }

        if (Schema::hasColumn('services', 'saloon_id')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropConstrainedForeignId('saloon_id');
            });
        }
    }

    private function copySaloonServicePivotsToServices(): void
    {
        if (! Schema::hasTable('salon_services')) {
            return;
        }

        $pivots = DB::table('salon_services')->get();

        foreach ($pivots as $pivot) {
            $service = DB::table('services')->where('id', $pivot->service_id)->first();

            if (! $service) {
                continue;
            }

            DB::table('services')->insert([
                'saloon_id' => $pivot->saloon_id,
                'name' => $service->name,
                'description' => $service->description,
                'price' => $pivot->price,
                'duration' => $pivot->duration,
                'image' => $service->image,
                'created_at' => $pivot->created_at ?? now(),
                'updated_at' => $pivot->updated_at ?? now(),
            ]);
        }
    }

    private function copyBookingItemsToBookings(): void
    {
        if (! Schema::hasTable('booking_items')) {
            return;
        }

        $bookingServices = DB::table('booking_items')
            ->select('booking_id', DB::raw('MIN(service_id) as service_id'))
            ->groupBy('booking_id')
            ->get();

        foreach ($bookingServices as $bookingService) {
            DB::table('bookings')
                ->where('id', $bookingService->booking_id)
                ->whereNull('service_id')
                ->update(['service_id' => $bookingService->service_id]);
        }
    }
};
