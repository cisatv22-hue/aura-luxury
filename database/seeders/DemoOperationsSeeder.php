<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Announcement;
use App\Models\Banner;
use App\Models\Bundle;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\HomeSection;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusChange;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\Review;
use App\Models\StockMovement;
use App\Models\Subscriber;
use App\Models\SizeGuide;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class DemoOperationsSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') && ! filter_var(env('AURA_DEMO_SEEDING'), FILTER_VALIDATE_BOOLEAN)) {
            throw new LogicException('En producción, permite esta carga solo con AURA_DEMO_SEEDING=true.');
        }

        DB::transaction(function (): void {
            $products = collect([
                'demo-sudadera-essentials',
                'demo-pantalon-streetwear',
                'demo-cadena-plata',
            ])->mapWithKeys(fn (string $slug): array => [$slug => Product::query()->where('slug', $slug)->firstOrFail()]);

            $variants = $products->mapWithKeys(fn (Product $product, string $slug): array => [
                $slug => ProductVariant::query()->where('product_id', $product->id)->where('is_active', true)->orderBy('id')->firstOrFail(),
            ]);

            foreach ($variants as $variant) {
                StockMovement::query()->firstOrCreate(
                    ['variant_id' => $variant->id, 'reference' => 'DEMO-SEED-STOCK'],
                    ['change' => 5, 'reason' => 'adjustment', 'note' => 'DEMO · Existencias sintéticas para revisión del panel.'],
                );
            }

            $customers = collect([
                ['key' => 'ana', 'name' => 'DEMO · Ana Torres', 'email' => 'ana.torres@aura.example.test', 'phone' => '+525500000101', 'city' => 'Ciudad de México'],
                ['key' => 'lucia', 'name' => 'DEMO · Lucía Reyes', 'email' => 'lucia.reyes@aura.example.test', 'phone' => '+525500000102', 'city' => 'Guadalajara'],
                ['key' => 'mateo', 'name' => 'DEMO · Mateo Cruz', 'email' => 'mateo.cruz@aura.example.test', 'phone' => '+525500000103', 'city' => 'Monterrey'],
            ])->mapWithKeys(fn (array $data): array => [
                $data['key'] => Customer::query()->updateOrCreate(
                    ['email' => $data['email']],
                    ['name' => $data['name'], 'phone' => $data['phone'], 'city' => $data['city'], 'notes' => 'DEMO · Registro ficticio para probar administración.'],
                ),
            ]);

            Address::query()->updateOrCreate(
                ['customer_id' => $customers['ana']->id, 'label' => 'DEMO · Casa'],
                ['recipient' => $customers['ana']->name, 'phone' => $customers['ana']->phone, 'postal_code' => '06000', 'state' => 'Ciudad de México', 'city' => 'Ciudad de México', 'neighborhood' => 'Centro', 'street' => 'Calle de muestra', 'exterior_number' => '123', 'references' => 'Dirección ficticia para pruebas.', 'is_default' => true],
            );

            $orders = [
                ['number' => 'AURA-9999-0001', 'customer' => 'ana', 'status' => Order::STATUS_NEW, 'product' => 'demo-sudadera-essentials', 'quantity' => 1],
                ['number' => 'AURA-9999-0002', 'customer' => 'lucia', 'status' => Order::STATUS_CONFIRMED, 'product' => 'demo-pantalon-streetwear', 'quantity' => 1],
                ['number' => 'AURA-9999-0003', 'customer' => 'mateo', 'status' => Order::STATUS_CANCELLED, 'product' => 'demo-cadena-plata', 'quantity' => 1],
            ];

            foreach ($orders as $data) {
                $product = $products[$data['product']];
                $variant = $variants[$data['product']];
                $unitPrice = $product->base_price_cents;
                $subtotal = $unitPrice * $data['quantity'];
                $confirmedAt = $data['status'] === Order::STATUS_CONFIRMED ? now()->subDays(2) : null;

                $order = Order::query()->updateOrCreate(
                    ['number' => $data['number']],
                    [
                        'customer_id' => $customers[$data['customer']]->id,
                        'status' => $data['status'],
                        'channel' => 'whatsapp',
                        'delivery_method' => 'shipping',
                        'delivery_zone' => 'DEMO · Por confirmar',
                        'delivery_postal_code' => '06000',
                        'customer_note' => 'DEMO · Pedido ficticio; no contactar ni preparar envío.',
                        'internal_note' => 'DEMO · No corresponde a una compra real ni a un pago recibido.',
                        'subtotal_cents' => $subtotal,
                        'shipping_cents' => 0,
                        'shipping_free' => false,
                        'shipping_needs_confirmation' => true,
                        'discount_cents' => 0,
                        'total_cents' => $subtotal,
                        'confirmed_at' => $confirmedAt,
                        'paid_at' => null,
                        'shipped_at' => null,
                        'delivered_at' => null,
                    ],
                );

                $order->items()->delete();
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'name' => $product->name,
                    'sku' => $variant->sku,
                    'color' => $variant->color?->name,
                    'size' => $variant->size,
                    'quantity' => $data['quantity'],
                    'unit_price_cents' => $unitPrice,
                    'line_total_cents' => $subtotal,
                ]);

                OrderStatusChange::query()->firstOrCreate(
                    ['order_id' => $order->id, 'to_status' => $data['status'], 'note' => 'DEMO · Estado inicial ficticio.'],
                    ['from_status' => null],
                );
            }

            Coupon::query()->updateOrCreate(
                ['code' => 'AURADEMO15'],
                ['type' => 'percent', 'value' => 1500, 'minimum_subtotal_cents' => 0, 'usage_limit' => 100, 'usage_count' => 0, 'starts_at' => null, 'ends_at' => null, 'is_active' => true],
            );

            Promotion::query()->updateOrCreate(
                ['name' => 'DEMO · Promoción de lanzamiento'],
                ['type' => 'percent', 'value' => 1000, 'priority' => 0, 'badge_text' => 'DEMO', 'starts_at' => null, 'ends_at' => null, 'is_active' => false],
            );

            Bundle::query()->updateOrCreate(
                ['slug' => 'demo-kit-urbano'],
                ['product_id' => null, 'name' => 'DEMO · Kit urbano (precio por definir)', 'price_cents' => 164000, 'is_active' => false],
            )->items()->delete();

            $bundle = Bundle::query()->where('slug', 'demo-kit-urbano')->firstOrFail();
            foreach (['demo-sudadera-essentials', 'demo-pantalon-streetwear'] as $slug) {
                $bundle->items()->create(['product_id' => $products[$slug]->id, 'variant_id' => $variants[$slug]->id, 'qty' => 1]);
            }

            Review::query()->updateOrCreate(
                ['product_id' => $products['demo-sudadera-essentials']->id, 'customer_id' => $customers['ana']->id],
                ['order_id' => null, 'rating' => 5, 'title' => 'DEMO · Reseña de muestra', 'body' => 'Reseña ficticia para probar moderación; no representa una compra real.', 'status' => 'pending'],
            );

            Banner::query()->updateOrCreate(
                ['title' => 'DEMO · Banner de temporada'],
                ['subtitle' => 'Contenido de prueba pendiente de aprobación.', 'image_path' => null, 'image_alt' => null, 'link_url' => null, 'position' => 99, 'starts_at' => null, 'ends_at' => null, 'is_active' => false],
            );
            Announcement::query()->updateOrCreate(
                ['message' => 'DEMO · Anuncio temporal de muestra'],
                ['link_url' => null, 'starts_at' => null, 'ends_at' => null, 'is_active' => false],
            );
            HomeSection::query()->updateOrCreate(
                ['title' => 'DEMO · Favoritos de AURA'],
                ['source_type' => 'new', 'source_config' => null, 'limit' => 3, 'position' => 99, 'is_visible' => false],
            );

            SizeGuide::query()->updateOrCreate(
                ['name' => 'DEMO · Guía de tallas (medidas por confirmar)'],
                ['unit' => 'cm', 'rows' => [['Talla' => 'S', 'Pecho' => 52], ['Talla' => 'M', 'Pecho' => 56]], 'is_visible' => false],
            );

            foreach (['demo-cliente-uno@aura.example.test', 'demo-cliente-dos@aura.example.test', 'demo-cliente-tres@aura.example.test'] as $email) {
                Subscriber::query()->updateOrCreate(['email' => $email], ['confirmed_at' => now(), 'unsubscribed_at' => null]);
            }
        });
    }
}
