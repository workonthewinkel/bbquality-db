<?php

    namespace BbqData\Models;

    use Carbon\Carbon;
    use BbqData\Contracts\Model;
    use BbqData\Helpers\Discount;
    use BbqData\Models\Casts\Json;
    use BbqData\Models\Scopes\CartScope;
    use Bbquality\Helpers\CarbonReduction;

    class Cart extends Model{

            
        /**
         * Order table
         *
         * @var string
         */
        protected $table = 'orders';


        /**
         * Guarded fields
         *
         * @var array
         */
        protected $guarded = ['id', 'customer_id'];
        
        /**
         * Cast the object field as a json
         *
         * @var array
         */
        protected $casts = [
            'shipping_info' => Json::class,
        ];
        
        /**
         * A cart has rows:
         *
         * @return void
         */
        public function rows()
        {
            return $this->hasMany('BbqData\Models\OrderRow', 'order_id', 'id' );

        }


        /**
         * A cart may have many coupons
         *
         * @return void
         */
        public function coupons()
        {
            return $this->belongsToMany('BbqData\Models\Coupon', 'bbquality_coupon_order', 'order_id', 'id' )
                        ->withPivot('status', 'amount', 'created_at', 'updated_at')
                        ->withTimestamps();
        }


        /**
         * A cart has a payment:
         *
         * @return void
         */
        public function payment()
        {
            return $this->hasOne('BbqData\Models\Payment', 'order_id', 'id' );
        }
        

        /**
         * Return the discounts
         *
         * @return array
         */
        public function getDiscountsAttribute()
        {
            return json_decode( $this->applied_discount, true ) ?? [];
        }

        /**
         * Return the subtotal that is applicable to discounts
         *
         * @return float
         */
        public function getDiscountApplicableSubtotalAttribute()
        {
            $subtotal = $this->subtotal_without_gift_certificates;

            //loop through rows:
            foreach( $this->rows as $row ){

                //check if the row has a discount_type:
                if( is_null( $row->discount_type ) ){
                    continue;   
                }
                
                //if it's in sale
                $subtotal -= ( $row->price * $row->quantity );

            }
                
            return $subtotal;
        }

        /**
         * Return the total without gift certificates:
         *
         * @return void
         */
        public function getSubtotalWithoutGiftcertificatesAttribute()
        {
            $subtotal = 0;
            $certificates = Coupon::getCertificateIds();

            foreach( $this->rows as $row ){

                //if this row has no price:
                if( !isset( $row->price ) || is_null( $row->price ) ){
                    continue;
                }

                //if it's a gift certificate: 
                if( in_array( $row->product_id, $certificates ) ){
                    continue;
                }

                //if it's charity:
                if( $row->product_id == CarbonReduction::getProduct() ){
                    continue;
                }
                
                //else, add it to the subtotal
                $price = ( $row->price * $row->quantity );
                $subtotal += $price;
            }

            return $subtotal;
        }


        /**
         * Check if the content of this cart is just certificates
         *
         * @return bool
         */
        public function only_has_certificates()
        {
            $certificate_ids = Coupon::getCertificateIds();

            //add the carbon product, because it doesn't count as a cart item:
            $certificate_ids[] = CarbonReduction::getProduct();
            
            foreach( $this->rows as $row ){
                if( !in_array( $row->product_id, $certificate_ids ) ){
                    return false;
                }
            }

            return true;
        }

        /**
         * Check if this cart is older then four hours
         *
         * @return boolean
         */
        public function is_old()
        {
            return ( $this->created_at->diffInHours( Carbon::now() ) > 4 );
        }

        /**
         * A check to see if a cart has free shipping:
         *
         * @return boolean
         */
        public function has_free_shipping()
        {
            //get total discount (e.g. second-half-price)
            $discount = 0;
            foreach( $this->rows as $row ){
                $discount += Discount::calculate( $row );
            }

            //check subtotal:
            //@todo put 75 in a Shipping helper
            if( $this->subtotal_without_giftcertificates - $discount >= 75 ){
                return true;
            }

            
         

            //check coupons:
            foreach( $this->discounts as $discount ){
                if( $discount['free_shipping'] || $discount['free_shipping'] == 1 ){
                    return true;
                }
            }

            //check product:
            //$this->setEnv();
            //$product_ids = explode( ',', $this->env['free_shipping_products'] );
            $product_ids = [126992,134091,134674,134667,134825,135269];

            foreach( $this->rows as $row ){
                if( in_array( $row->product_id, $product_ids ) ){
                    return true;
                }
            }

            //check membership:
            if( !is_null( User::current() ) && !is_null( User::current()->membership ) ){
                return true; 
            }

            if( Carbon::now() > '2023-03-03 23:59:59' && Carbon::now() < '2023-03-06 00:00:00') {
                return true;
            }

            return false;
        }


        /**
         * Attach a coupon to this cart
         *
         * @param Coupon $coupon
         * @return void
         */
        public function add_discount( $coupon )
        {
            $discounts = $this->discounts ?? [];

            //setup the new discount:
            $discounts[] = [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'amount' => $coupon->amount,
                'type' => $coupon->type,
                'free_shipping' => $coupon->free_shipping,
                'gift_certificate' => $coupon->is_gift_certificate,
            ];

            //add it as a json to the order:
            $this->applied_discount = json_encode( $discounts );
            $this->save();
        }


        /**
         * Remove a discount
         *
         * @param Coupon $coupon
         * @return void
         */
        public function remove_discount( $coupon )
        {
            $discounts = $this->discounts ?? [];
            $new_discounts = [];
            foreach( $discounts as $discount ){
                if( $discount['code'] !== $coupon->code ){
                    $new_discounts[] = $discount;
                }
            }

            $this->applied_discount = json_encode( $new_discounts );
            $this->save();
        }


        /**
         * Custom boot function
         *
         * @return void
         */
        public static function boot() {
            
            parent::boot();
            static::addGlobalScope( new CartScope() );
            
            self::deleting( function( $order ) { 

                $order->rows()->each(function( $row ) {
                    $row->delete(); // <-- direct deletion
                });

                $order->payment()->each(function( $payment ) {
                    $payment->delete(); 
                });

                $order->customer()->each(function( $customer ) {
                    $customer->delete(); 
                });
            });
        }
        
    }