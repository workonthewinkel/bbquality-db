<?php

    namespace BbqData\Models;

	use Carbon\Carbon;
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
            return $this->hasOne( Order::class );
        }


        /**
         * A customer may have a user
         *
         * @return void
         */
        public function user()
        {
            return $this->hasOne( User::class, 'ID', 'user_id' );    
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
                        ->orderBy('created_at', 'DESC');

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
		 * Check if this customer is a returning customer
		 *
		 * @return bool
		 */
		public function getIsReturningAttribute(): bool
		{
			//first see if we have a hard user_id to match on,
			//but default to email:
			$clause = 'email';
			$value = $this->email;
			if( !is_null( $this->user_id ) ){
				$clause = 'user_id';
				$value = $this->user_id;
			}

			//then query the result
			$result = $this->where( $clause, $value )
				 		   ->where( 'created_at', '>=', Carbon::now()->subMonths( 3 ) )
				 		   ->where( 'id', '!=', $this->id )
				 		   ->get();
			
			//if there's nothing found, this is not a returning customer
			if( $result->isEmpty() ){
				return false;
			}

			
			return true;
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
                if( $this->{$key} == '' || is_null( $this->{$key} ) || ctype_space( $this->{$key} ) ){
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
         * Get the international prefixed phone number
         *
         * @return void
         */
        public function getInternationalPhoneAttribute()
        {
            if( !is_null( $this->phone ) ){

                $country = static::get_country_code( $this->country );
                $codes = [ 'NL' => '+31', 'BE' => '+32' ];
                $alts = ['NL' => '0031', 'BE' => '0032' ]; 

                if( 
                    substr( $this->phone, 0, 3 ) !== $codes[ $country ] && 
                    substr( $this->phone, 0, 4 ) !== $alts[ $country ]
                ){
                    $phone = $this->phone;
                    
                    if( substr( $this->phone, 0, 1 ) == 0 ){
                        $phone = substr( $this->phone, 1 );
                    }

                    return $codes[ $country ] . str_replace(' ', '', $phone );
                
                }

                return str_replace( $alts[ $country], $codes[ $country], $this->phone );
            }

            return null;
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
