<?php

    namespace BbqData\Models;

    use Carbon\Carbon;
    use BbqData\Helpers\Price;
    use BbqData\Contracts\Model;
    use BbqData\Models\Coupon;
    use BbqData\Helpers\Discount;
    use BbqData\Models\Casts\Json;
    use BbqOrders\Helpers\Shipping;
    use BbqData\Models\CouponCampaign;
    use BbqData\Models\Scopes\NotCartScope;
    use Bbquality\Helpers\CarbonReduction;

    class Order extends Model{
        
    
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
         * The courrier string depening on the shipping_key
         *
         * @var array
        */
        protected $appends = ['courrier'];
                
        
        /**
         * An order has rows:
         *
         * @return void
         */
        public function rows()
        {
            return $this->hasMany('BbqData\Models\OrderRow');

        }


        /**
         * And order has a customer:
         *
         * @return void
         */
        public function customer()
        {
            return $this->belongsTo('BbqData\Models\Customer');
        }

        /**
         * An order has a payment:
         *
         * @return void
         */
        public function payment()
        {
            return $this->hasOne('BbqData\Models\Payment');
        }


        /**
         * An order may have many coupons
         *
         * @return void
         */
        public function coupons()
        {
            return $this->belongsToMany('BbqData\Models\Coupon', 'bbquality_coupon_order' )
                        ->withPivot('status', 'amount', 'created_at', 'updated_at')
                        ->withTimestamps();
        }


        /**
         * An order may belong to an affiliate  
         *
         * @return Affiliate
         */
        public function affiliate()
        {
            return $this->belongsTo('BbqData\Models\Affiliate');
        }


        /**
         * Set this orders status, by the status of the payment
         *
         * @return String
         */
        public function setStatusByPayment()
        {            
            switch( $this->payment->status ){
                case 'paid':
                    $this->status = 'processing';
                    break;
                case 'pending':
                case 'authorized':
                    $this->status = 'on-hold';
                    break;
                case 'expired':
                case 'failed':
                    $this->status = 'canceled';
                    break;
                case 'refund':
                case 'chargedback':
                case 'partial_refunded':
                    $this->status = 'refund';
                    break;
                default:
                    //default: just take the payment's status. Valid for:
                    //open, cancelled
                    $this->status = $this->payment->status;
                    break;
            }

            return $this;
        }
        


        /**
         * Check if this order has open notifications it needs to send
         *
         * @return boolean
         */
        public function hasOpenNotifications()
        {
            if( 
                $this->buyer_notifications_sent == false ||
                $this->seller_notifications_sent == false
            ){
                return true;
            }

            return false;
        }

        
        /**
         * Return the orders' totals
         *
         * @return void
         */
        public function getTotals( $raw = false )
        {
            $subtotal = 0;
            $vat = 0;
            $vatHigh = 0;
            $vatLow = 0;
            $subtotalHigh = 0;
            $subtotalLow = 0;         

            foreach( $this->rows as $row ){
                if( !isset( $row->price ) || is_null( $row->price ) ){
                    continue;
                }

                $price = ( $row->price * $row->quantity );
                $subtotal += $price;
                $vat += Price::vat( $price, $row->vat );
                if ($row->vat > 0.1) {
                    $subtotalHigh += $price;
                    $vatHigh += Price::vat( $price, $row->vat );
                } else {
                    $subtotalLow += $price;
                    $vatLow += Price::vat( $price, $row->vat );
                }

                //subtract stacked discounts:
                $subtotal -= Discount::calculate( $row );
            }

            //shipping & payment costs, also add their vat:
            $shipping = $this->shipping['price_raw'] ?? 0;
            $transaction_cost = $this->payment->transaction_cost ?? 0;
            $vat += Price::vat( $shipping, 0.21 );
            $vat += Price::vat( $transaction_cost, 0.21 );

            //discount & gift certificates APPLIED
            $discount = $this->discount_total * -1;
            $gift_certificates = $this->gift_certificates_total * -1;
            $exact_certificate_rectification = $this->api_certificates * -1;

            $total = $subtotal + $shipping + $discount + $gift_certificates + $transaction_cost;

            if( $total < 0 ){
                $total = 0;
            }

            //if we want just the raw numbers:
            if( $raw ){
                return [
                    'subtotal' => $subtotal,
                    'subtotal-high' => $subtotalHigh,
                    'subtotal-low' => $subtotalLow,
                    'shipping' => $shipping,
                    'transaction-cost' => $transaction_cost,
                    'vat' => $vat,
                    'vat-high' => $vatHigh,
                    'vat-low' => $vatLow,
                    'discount' => $discount,
                    'gift-certificates' => $gift_certificates,
                    'exact-certificate-rectification' => $exact_certificate_rectification,
                    'total' => $total,
                ];
            }

            //else, we're looking for formatted html:
            $totals = [
                'Subtotaal'         => Price::format( $subtotal ),
                'Waarvan BTW'       => Price::format( $vat ),
                'Verzendkosten'     => Price::format( $shipping )
            ];

            if( $transaction_cost !== 0 ){
                $totals['Transactiekosten'] = Price::format( $transaction_cost );
            }

            if( $discount !== 0 ){
                $totals['Korting'] = Price::format( $discount );
            }

            if( $gift_certificates !== 0 ){
                $totals['Cadeaukaarten'] = Price::format( $gift_certificates );
            }

            $totals['Totaal'] = Price::format( $total );
            return $totals;
            
        }

        /**
         * Get the subtotal of certificate money (bought certificates)
         *
         * @return float
         */
        public function getCertificateTotal()
        {
            $certificateTotal = 0;
            $certificates = Coupon::getCertificateIds();

            //check if this is a gift-cerificate:
            foreach( $this->rows as $row ){
                if( in_array( $row->product_id, $certificates ) ){
                    $price = ( $row->price * $row->quantity );
                    $certificateTotal += $price;
                }
            }

            return $certificateTotal;
        }


        /**
         * Return the subtotal that is applicable for promotions:
         *
         * @return float
         */
        public function getPromotionalSubtotalAttribute()
        {
            $totals = $this->getTotals( true );
            $gift_certificates = $this->getCertificateTotal();

            //only subtract gift certificates if we're not redeeming them:
            //remove gift certificates, they don't count:
            $subtotal = $totals['subtotal'] - $gift_certificates;

            //substract applied discounts (all applied coupons which are not giftcertificates)
            $subtotal -= $this->discount_total;

            //loop through rows:
            foreach( $this->rows as $row ){
                //if it's charity, subtract the total:
                if( $row->product_id == CarbonReduction::getProduct() ){
                    $subtotal -= $row->total;
                    continue;                
                }
            }
                
            return $subtotal;
        }

        /**
         * Return the courrier this is contructed using the shipping_key attribute
         *
         * @return String
         */
        public function getCourrierAttribute() 
        {
            switch( $this->shipping_key )  {
                case 'evening-delivery-trunkrs' :
                    return 'trunkrs-evening';
                break;
                case 'evening-delivery' :
                    return 'trunkrs-evening';
                break;
                case 'belgium-delivery' :
                    return 'trunkrs-belgium';
                break;
                case 'chilled-delivery' :
                    return 'chill-bill';
                break;
                case 'evening-delivery-chill-bill' :
                    return 'chill-bill-evening';
                break;
                case 'day-delivery-chill-bill' :
                    return 'chill-bill-day';
                break;
                default:
                    return $this->shipping_key;
                break;
            }
        }
        

        /**
         * Reduce stocks:
         *
         * @return void
         */
        public function reduceStock()
        {
            foreach( $this->rows as $row ){
                if( $row->stock_reduced == false ){
                    $row->stock()->reduce( $row->quantity );
                    $row->stock_reduced = true;
                    $row->save();
                }
            }
        }


        /**
         * Turn this orders status in a css class
         *
         * @return string
         */
        public function getBadgeAttribute()
        {
            switch( $this->status ){

                case 'processing':
                    return 'info';
                    break;
                case 'succesfull':
                    return 'success';
                    break;
                case 'canceled':
                case 'cancelled':
                    return 'danger';
                    break;
                case 'on-hold':
                case 'open':
                case 'refund':
                    return 'warning';
                    break;
                default:
                    return $this->status;
                    break;
            }
        }

        /**
         * Formatted delivery date:
         *
         * @return void
         */
        public function getDeliveryDateAttribute()
        {
            $time = $this->delivery_day;
            return Carbon::createFromTimestamp( $time );
        }


        /**
         * Return the shipping attribute
         *
         * @return Array
         */
        public function getShippingAttribute() 
        {
            $info = $this->shipping_info;
            if( is_string( $info ) ){ 
                return json_decode( $info, true );    
            }

            return $info;
        }

        /**
         * Return the delivery time of an order
         *
         * @return string
         */
        public function getDeliveryTimeAttribute()
        {
            $delivery_day = $this->delivery_date->dayOfWeek;
            $formatted = $this->delivery_date->format('Y-m-d');

            $slug = $this->shipping['slug'];
            $methods = Shipping::methods();
            if( !isset( $methods[ $slug ] ) ){
                return null;
            }

            if( array_key_exists( $formatted, $methods[ $slug ]['availability']) ) {
                return $methods[ $slug ]['availability'][ $formatted ];
            }  
            if( isset( $methods[ $slug ] ) && is_int( $delivery_day ) ){
                return $methods[ $slug ]['availability'][ $delivery_day ];
            }

            return null;
        }


        /**
         * Returns the current shipping state
         *
         * @return string
         */
        public function getShippingStateAttribute()
        {
            if( $this->delivery_day == strtotime( date( 'Y-m-d 00:00:00' ) ) && $this->label_path !== '' ){
                return 'Onderweg';
            }else if( $this->status == 'open' ){
                return '';
            }else if( $this->delivery_day < strtotime('+1 day') && $this->label_path !== '' ){
                return 'Geleverd';
            }else if( $this->label_path !== '' ){
                return 'Staat klaar voor verzending';
            }else{
                return 'Bestelling ontvangen';
            }
        }

        
        /**
         * Return the Discount attribute
         *
         * @return Array
         */
        public function getDiscountTotalAttribute()
        {
            $total = 0;
            foreach( $this->discounts as $discount ){
                if( $discount['gift_certificate'] == false ){

                    $key = ( isset( $discount['calculated_amount'] ) ? 'calculated_amount' : 'amount' );
                    $total += $discount[ $key ] ?? 0;
                }
            }
            
            return $total;
        }

        /**
         * Return the certificates created through the api
         * 
         * @return Int
         */
        public function getApiCertificatesAttribute()
        {
            $total = 0;
            foreach( $this->discounts as $discount ){
                if( $discount['gift_certificate'] == true ){
                    $id = $discount['id'];
                    $coupon = Coupon::find($id);
                    if ( !is_null( $coupon ) && !is_null($coupon->coupon_campaign_id)) {
                        $campaign = CouponCampaign::find( $coupon->coupon_campaign_id );
                        if ( !is_null( $campaign ) && !is_null($campaign) && $campaign->source == 'api' ) {
                            $key = ( isset( $discount['calculated_amount'] ) ? 'calculated_amount' : 'amount' );
                            $total += $discount[ $key ] ?? 0;
                        }
                    }
                }
            }
            return $total;
        }

        /**
         * Return the (SPENT) Gift Certificate total attribute
         *
         * @return Array
         */
        public function getGiftCertificatesTotalAttribute()
        {
            $total = 0;
            foreach( $this->discounts as $discount ){
                if( $discount['gift_certificate'] == true ){

                    $key = ( isset( $discount['calculated_amount'] ) ? 'calculated_amount' : 'amount' );
                    $total += $discount[ $key ] ?? 0;
                }
            }
            
            return $total;
        }

        /**
         * Return the discounts used:
         *
         * @return void
         */
        public function getDiscountsAttribute()
        {
			if( is_null( $this->applied_discount ) ){
				return [];
			}
			
            return json_decode( $this->applied_discount, true ) ?? [];
        }

        /**
         * Return the shipping method
         *
         * @return string
         */
        public function getShippingMethodAttribute()
        {
            $method = $this->shipping;
            return $method['name'] ?? 'Onbekend';
        }


        /**
         * Return the readable status:
         *
         * @return String
         */
        public function getReadableStatusAttribute()
        {
            $states = [
                'processing' => "In behandeling",
                'open'       => "Open",
                'on-hold'    => "In de wacht",
                'cancelled'  => "Geannuleerd",
                'canceled'   => 'Geannuleerd',
                'failed'     => "Mislukt",
                'completed'  => "Voltooid",
                'refunded'   => '(deels) terugbetaald',
            ];

            return $states[ $this->status ];
        }

        /**
         * Return the shipping class
         *
         * @return string
         */
        public function getShippingClassAttribute()
        {
            $classes = [
                'evening-delivery' => 'badge-dark',
                'day-delivery' => 'badge-secondary',
                'belgium-delivery' => 'badge-secondary',
                'free-delivery' => 'badge-success',
                'local-delivery' => 'badge-success',
                'pickup-basbq' => 'badge-warning',
                'pickup-carmedia' => 'badge-danger',
                'pickup-keischerp' => 'badge-dark',         
            ];

            if( isset( $classes[ $this->shipping_key ] ) ){
                return $classes[ $this->shipping_key ];
            }

            return 'badge-'.str_replace( '_', '-', $this->shipping_key );
        }


        /**
         * Does this order have a label?
         *
         * @return String
         */
        public function getHasLabelAttribute()
        {
            if( !is_null( $this->label_path ) && $this->label_path != '' ){
                return true;
            }

            return false;
        }

    
        /**
         * Return the correct label API
         *
         * @return String
         */
        public function getLabelApiAttribute()
        {
            if( 
                $this->shipping_key === 'evening-delivery' ||
                $this->shipping_key === 'evening-delivery-trunkrs' ||
                $this->shipping_key === 'day-delivery' ||
                $this->shipping_key === 'belgium-delivery'
            ){
                return 'pakketpartner';

            }else if( 
                $this->shipping_key === 'chilled-delivery' ||
                $this->shipping_key === 'evening-delivery-chill-bill' ||
                $this->shipping_key === 'day-delivery-chill-bill'
            ){
				return 'chillbill';
			
			}

            return 'bbquality';
        }


        /**
         * Get the certificates, if there are any:
         * 
         * @return bool
         */
        public function getHasCertificatesAttribute()
        {
            $rows = $this->rows;
            $ids = Coupon::getCertificateIds();
            foreach( $rows as $row ){
                if( in_array( $row->product_id, $ids ) ){
                    return true;
                    break;
                }
            }

            return false;
        }

        /**
         * Return the certificate url   
         *
         * @return void
         */
        public function getCertificatesUrlAttribute()
        {
            $token = \md5(\json_encode( $this->coupons()->printable() ) );
            return '/download-cadeaubonnen?order='.$this->id.'&token='.$token;
        }


        /**
         * Return wether or not this order is a subscription
         *
         * @return void
         */
        public function getIsSubscriptionAttribute()
        {
            if( !is_null( $this->payment ) ){
                return $this->payment->is_recurring;
            }

            return false;
        }

        /**
         * Return the total points given in this order
         *
         * @return int
         */
        public function getTotalPointsAttribute()
        {
            $totals = $this->getTotals( true );
			$subtotal = $totals['subtotal'] - $this->getCertificateTotal() + $totals['discount'];
            $points = floor( $subtotal / 25 ); //base points
            foreach( $this->rows as $row ){
                $points += ($row->points_earned * $row->quantity);
            }
            return $points;
        }
        /**
         * Return the next order number
         *
         * @return int
         */
        public static function nextOrderNumber()
        {
            $q = static::table('orders')->whereNotNull('order_number')->orderBy('order_number', 'DESC');
            $last_order = $q->first();
            $current = (int)$last_order->order_number;
            $new = ( $current + 1 );

            //always check if this order number already exists:
            if( static::table('orders')->where('order_number', $new )->first() !== null ){
                return static::nextOrderNumber();
            }

            return $new;
        }


        /**
         * Return the source id
         *
         * @return int
         */
        public static function get_source_id( string $name = null ): int {
            // return the source id or 1 if the source is not found
            return static::sources()[ $name ] ?? 1;
        }

        /**
         * Return the possible sources  
         */
        public static function sources(): array {
            return [
                'bbquality.nl' => 1,
                'paymentlink' => 2
            ];
        }

        /**
         * Return the source as an object
         *
         * @return stdClass
         */
        public function getSourceAttribute(): stdClass {

            $id = $this->source_id;

            return (object) [
                'id' => $id,
                'name' => static::sources( $id )
            ];
        }

        /**
         * Custom boot function
         *
         * @return void
         */
        public static function boot() {
            
            parent::boot();
            //static::addGlobalScope( new NotCartScope() );
            
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
