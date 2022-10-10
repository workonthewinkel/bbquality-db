<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;

    class Customer extends Model
    {
        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'customers';

        /**
         * Only the id and order_id are guarded
         *
         * @var array
         */
        protected $guarded = ['id'];


        /**
         * An order has a payment:
         *
         * @return void
         */
        public function order()
        {
            return $this->hasOne('BbqData\Models\Order');
        }


        /**
         * A customer may have a user
         *
         * @return void
         */
        public function user()
        {
            return $this->hasOne('BbqData\Models\User', 'ID', 'user_id' );    
        }


        /**
         * Query orders made by a specific user
         *
         * @param Int $user_id
         * @return void
         */
        public static function ordersByUser( $user_id )
        {
            //fetch customer ids:
            $customers = static::where('user_id', $user_id )->get()->pluck('id')->all();

            if( empty( $customers ) ){
                return \collect([]);
            }

            //then return orders: 
            $orders = Order::where('status', '!=', 'canceled')
                        ->where('status', '!=', 'cancelled' )
                        ->where('status', '!=', 'cart' )
                        ->where('status', '!=', 'new' )
                        ->whereIn( 'customer_id', $customers )
                        ->with('rows','payment')    
                        ->orderBy('created_at', 'DESC')
                        ->get();
            return $orders;
        }

        /**
         * Return the name of this customer
         *
         * @return string
         */
        public function getNameAttribute()
        {
            return $this->first_name.' '.$this->last_name;
        }


        /**
         * Check if a customer is unique in the database
         *
         * @return bool
         */
        public function getFirstOrderAttribute()
        {
            $amount = $this->where('email', $this->email)->count();
            if( $amount <= 1 ){
                return true;
            }else{
                return false;
            }
        }

        /**
         * Get the shipping name
         *
         * @return string
         */
        public function getShippingNameAttribute()
        {
            if( $this->shipping_first_name != '' && $this->shipping_last_name != '' ){
                return $this->shipping_first_name.' '.$this->shipping_last_name;
            }else{
                return $this->name;
            }
        }

        /**
         * Return regular address:
         *
         * @return String
         */
        public function getFullAddressAttribute()
        {
            $string = stripslashes( $this->address ).'<br/>';
            $string .= $this->zipcode.' '.stripslashes( $this->city );
            $string .= '<br/>'.stripslashes( $this->country );
            return $string;
        }

        /**
         * Return the Affiliation attribute 
         * (for Google Collect)
         *
         * @return String
         */
        public function getAffiliationAttribute()
        {
            return $this->address.' '.$this->city;
        }

        /**
         * Return full shipping address
         *
         * @return String
         */
        public function getFullShippingAddressAttribute()
        {
            $shipping = $this->shipping_info();
            $string = $shipping['address'].'<br/>';
            $string .= $shipping['zipcode'].' '. $shipping['city'];
            $string .= '<br/>'. $shipping['country'];
            return $string;
        }


        /**
         * Return the correct shipping info
         *
         * @return Array
         */
        public function shipping_info()
        {
            $response = [];
            $props = [ 'address', 'zipcode', 'city', 'country' ];
            foreach( $props as $prop ){

                $key = 'shipping_'.$prop;
                $value = $this->{$key};
                if( $this->{$key} == '' || $this->{$key} == ' ' || is_null( $this->{$key} ) ){
                    $value = $this->{$prop};
                }
                
                //overwrite default country:
                if( $key == 'shipping_country' && $this->shipping_address === '' ){
                    $value = $this->country;
                }

                $response[ $prop ] = stripslashes( $value );
                
                if( $prop == 'country' ){
                    $response['country-code'] = static::get_country_code( $value );
                }
            }

            return $response;
        }


        /**
         * Get Country Code attribue
         *
         * @return void
         */
        public function getCountryCodeAttribute()
        {
            return static::get_country_code( $this->country );
        }



        /**
		 * Returns the country code
		 *
		 * @param Array $user
		 * 		
		 * @return void
		 */
		public static function get_country_code( $country ){
		
			switch( strtolower( $country ) ){

				case 'nederland':
				case 'netherlands':
                case 'the netherlands':
                case 'nl':

					return 'NL';
					break;
				
				case 'belgium':
				case 'belgie':
                case 'belgië':
                case 'be':

					return 'BE';
					break;

				case 'duitsland':
				case 'germany':
                case 'deutschland':
                case 'de':

					return 'DE';
					break;

				case 'luxemburg':
                case 'luxembourg':
                case 'lu':
					
					return 'LU';
					break;

            }
            
            return null;
        }
        
    }
