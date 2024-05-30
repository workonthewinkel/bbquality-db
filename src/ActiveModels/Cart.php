<?php

    namespace BbqData\ActiveModels;

    use Carbon\Carbon;
    use BbqData\Models\User;
    use BbqData\Models\Coupon;
    use BbqData\Contracts\ActiveModel;
    use Bbquality\Helpers\CarbonReduction;


    class Cart extends ActiveModel{

        /**
         * Data object
         *
         * @var array
         */
        protected $data;

        /**
         * Local cart_id
         *
         * @var uuid 
         */
        protected $cart_id;

        /**
         * Constructor
         *
         * @param Int $cart_id
         */
        public function __construct( $cart_id )
        {
            $this->connect_collection();
            $this->cart_id = $cart_id;
            if( $this->valid_id( $cart_id ) ){
                $this->data = $this->fetch_data( $cart_id );
            }else{
            }
        }     
        
        /**
         * Return the collection name for this active model
         *
         * @return string
         */
        public static function get_collection_name() 
        {
            return 'carts';   
        }

        /*====================================*/
        /*          Getters                   */
        /*====================================*/

        /**
         * Return any data in the data-array
         *
         * @return mixed
         */
        public function get( $key, $default = null )
        {
            if( isset( $this->data[ $key ] ) ){
                return $this->data[ $key ];
            }

            return $default;
        }


        /**
         * Return the subtotal without gift certificates
         *
         * @return void
         */
        public function get_subtotal_without_gift_certificates()
        {
            $subtotal = 0;
            $certificates = Coupon::getCertificateIds();

            foreach( $this->get('rows',[]) as $row ){

                //if this row has no price:
                if( !isset( $row['price'] ) || is_null( $row['price'] ) || $row['price'] == 0 ){
                    continue;
                }

                //if it's a gift certificate: 
                if( in_array( $row['id'], $certificates ) ){
                    continue;
                }

                //if it's charity:
                if( $row['id'] == CarbonReduction::getProduct() ){
                    continue;
                }
                
                //else, add it to the subtotal
                $subtotal += ( $row['price'] * $row['quantity'] );
            }

            return $subtotal;
        }


        /**
         * Calculate over which amount discounts can be calculated
         *
         * @return void
         */
        public function get_discount_applicable_subtotal()
        {
            $subtotal = $this->get_subtotal_without_gift_certificates();

            //loop through rows:
            foreach( $this->get('rows', []) as $row ){

                //check if the row has a discount_type:
                if( is_null( $row['discount_type'] ) || $row['discount_type'] == '' ){
                    continue;   
                }

                //if it's charity:
                if( $row['id'] == CarbonReduction::getProduct() ){
                    continue;
                }
                
                //if it's in sale
                $subtotal -= ( $row['price'] * $row['quantity'] );

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

            $rows = $this->get('rows', []);
            if( empty( $rows ) ){
                return false;
            }

            foreach( $rows as $row ){
                if( !in_array( $row['id'], $certificate_ids ) ){
                    return false;
                }
            }

            return true;
        }

        /**
         * A check to see if a cart has free shipping:
         *
         * @return boolean
         */
        public function has_free_shipping()
        {
            //check subtotal:
            //@todo put 75 in a Shipping helper
            if( $this->get_subtotal_without_gift_certificates() >= 75 ){
                return true;
            }

            //check coupons:
            foreach( $this->get('discounts', []) as $discount ){
                if( $discount['free_shipping'] || $discount['free_shipping'] == 1 ){
                    return true;
                }
            }

            //check product:
            $product_ids = explode( ',', env('free_shipping_products') );

            foreach( $this->get('rows',[]) as $row ){
                if( in_array( $row['id'], $product_ids ) ){
                    return true;
                }

                if( $row['points_spent'] > 0 ){
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


        /*====================================*/
        /*          Discounts                 */
        /*====================================*/


        /**
         * Attach a coupon to this cart
         *
         * @param Coupon $coupon
         * @return void
         */
        public function add_discount( $coupon )
        {
            $data = $this->data;
            $discounts = $this->get( 'discounts', [] );

            //setup the new discount:
            $discounts[] = [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'amount' => $coupon->amount,
                'type' => $coupon->type,
                'free_shipping' => $coupon->free_shipping,
                'gift_certificate' => $coupon->is_gift_certificate,
                'coupon_campaign_id' => $coupon->coupon_campaign_id,
            ];

            //add it as a json to the order:
            $data['discounts'] = $discounts;
            $this->save( $data );
        }


        /**
         * Remove a discount
         *
         * @param Coupon $coupon
         * @return void
         */
        public function remove_discount( $coupon )
        {
            $data = $this->data;
            $discounts = $this->get('discounts', [] );
            $new_discounts = [];
            foreach( $discounts as $discount ){
                if( $discount['code'] !== $coupon->code ){
                    $new_discounts[] = $discount;
                }
            }

            $data['discounts'] = $new_discounts;
            $this->save( $data );
        }


        /**
         * Returns wether or not a cart is new or old:
         *
         * @return boolean
         */
        public function is_old()
        {
			$created_at = $this->get( 'created_at', null );
            $updated_at = $this->get( 'updated_at', null );

			//always check the created_at first. A cart is old if it's been over 30 minutes:
			if( !is_null( $created_at ) && Carbon::createFromTimestamp( $created_at )->diffInMinutes( Carbon::now() ) > 30 ){
				return true;
			}

			//fallback to the updated_at:
            if( !is_null( $updated_at ) && Carbon::createFromTimestamp( $updated_at )->diffInMinutes( Carbon::now() ) > 30 ){
                return true;
            }

            return false;
        }


        /**
         * Check if this cart contains products with points
         *
         * @return void
         */
        public function contains_points()
        {
            $rows = $this->get('rows', []);
            foreach( $rows as $row ){
                if( !is_null( $row ) && \absint( $row['points_spent'] ) > 0 ){
                    return true;
                }
            }   

            return false;
        }



        /*====================================*/
        /*          Helper functions          */
        /*====================================*/


        /**
         * Save a single field
         *
         * @param string $key
         * @param mixed $value
         * @return mixed
         */
        public function save_field( $key, $value )
        {
            $data = $this->data;
            $data[ $key ] = $value;
            return $this->save( $data );
        }


        /**
         * Save this cart with fresh data
         *
         * @param Array $data
         * @return void
         */
        public function save( $data )
        {
            //set the delete_after and updated_at properties:
            $data['delete_after'] = static::delete_after();
            $data['updated_at'] = Carbon::now()->timestamp;

            //set the data, so we can use it later.
            $this->data = $data;

            //and save the cart
            return $this->save_data( $this->cart_id, $data );
        }

        /**
         * Does this cart exist?
         *
         * @return boolean
         */
        public function exists()
        {
            return ( empty( $this->data ) ? false : true );
        }

        /**
         * Create a new cart in Mongo,
         * Send back the ID:
         */
        public static function create() 
        {
            $result = static::get_collection( 'carts' )->insertOne([
                'order_id' => 0,
                'rows' => [],
                'analytics' => [],
                'discounts' => [],
                'delete_after' => static::delete_after(),
				'updated_at' => Carbon::now()->timestamp,
				'created_at' => Carbon::now()->timestamp,
            ]);

            return (string) $result->getInsertedId();
        }   


        /**
         * Find a cart based on order ID
         *
         * @param int $order_id
         * @return Cart
         */
        public static function find( $order_id ) 
        {
            $result = static::get_collection()->findOne([
                'order_id' => $order_id
            ]);

            if( !is_null( $result ) ){
                $cart_id = $result->_id->__toString();
                return new self( $cart_id );
            }

            return null;
        }


        /**
         * Get the delete after date
         *
         * @return int (timestamp)
         */
        public static function delete_after()
        {
            return Carbon::now()->addWeeks( 2 )->timestamp;   
        }

       
    }
