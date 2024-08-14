<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;
    use BbqData\Helpers\Fields;
    use BbqData\Models\Customer;

    class User extends Model
    {
        
        /**
         * Don't keep timestamps
         *
         * @var boolean
         */
        public $timestamps = false;

        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'users';

        /**
         * Only the id is guarded
         *
         * @var array
         */
        protected $guarded = ['id'];

        /**
         * Return this users membership(s)
         *
         * @return Membership
         */
        public function membership()
        {
            return $this->hasOne( Membership::class, 'user_id', 'ID' );
        }


        /**
         * A user has many customer records
         *
         * @return Customer
         */
        public function customers()
        {
            return $this->hasMany( Customer::class , 'user_id', 'ID' );
        }


        /**
         * A loyalty record has a user
         *
         * @return void
         */
        public function loyalty()
        {
            return $this->hasOne( Loyalty::class, 'user_id', 'ID' );
        }

		/**
		 * A user has many reviews
		 *
		 * @return void
		 */
		public function reviews()
		{
			return $this->hasMany( Review::class, 'user_id', 'ID' );
		}


        /**
         * Get current logged in user
         *
         * @return User
         */
        public static function current()
        {
            $id = static::current_id();
            if( !is_null( $id ) ){
                return static::find( $id );
            }

            return null;
        }


        /**
         * Return the current ID
         *
         * @return void
         */
        public static function current_id()
        {
            return get_current_user_id();
        }


        /**
         * Return the full name of this user
         *
         * @return string
         */
        public function getFullNameAttribute()
        {
            $meta = $this->meta;
            $name = $meta['first_name'] ?? '';
            $name .= ' '. $meta['last_name'] ?? '';
            return $name;
        }


        /**
         * Return the address fields of a user
         *
         * @return void
         */
        public function getMetaAttribute()
        {
            //get fields from user-meta and the nearest customer
            $meta = \get_user_meta( $this->ID );
            $allowed_fields = array_keys( Fields::all() );
            $not_allowed = Fields::shipping();

            foreach( $allowed_fields as $field ){
                
                if( in_array( $field, $not_allowed ) || $field == 'customer_remarks' ){
                    continue;
                }

                //check meta values: 
                if( 
                    isset( $meta[ $field ][0] ) && 
                    $meta[ $field ][0] != '' &&
                    $meta[ $field ][0] != false &&
                    !is_null( $meta[ $field ][0] )
                ){
                    $defaults[ $field ] = $meta[ $field ][0];
                    //$defaults[ $field ] = '';
                }
            }

            return $defaults;
        }


        /**
         * Check if a user is logged in
         *
         * @return bool
         */
        public static function loggedIn()
        {
            return is_user_logged_in();
        }


        /**
         * Add points to the loyalty of this user:
         *
         * @param int $add
         * @return void
         */
        public function add_points( $add )
        {
            $loyalty = Loyalty::where('user_id', $this->ID )->first();
            if( is_null( $loyalty ) ){
                $loyalty = $this->loyalty()->create();
            }

            $total = $loyalty->points_saved;
            $balance = $loyalty->points_balance;

            $loyalty->points_saved = absint( ( $total + $add ) );
            $loyalty->points_balance = absint( ( $balance + $add ) );
            $loyalty->save();
        }


        /**
         * Subtract points to the loyalty of this user:
         *
         * @param int $sub
         * @return void
         */
        public function sub_points( $sub )
        {
            $loyalty = $this->loyalty;
            if( is_null( $loyalty ) ){
                $loyalty = $this->loyalty()->create();
            }

            $total = $loyalty->points_spent;
            $balance = $loyalty->points_balance;

            $loyalty->points_spent = intval( ( $total + $sub ) );
            $loyalty->points_balance = intval( ( $balance - $sub ) );
            $loyalty->save();
        }
    }
