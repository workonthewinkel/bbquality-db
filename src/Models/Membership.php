<?php 

    namespace BbqData\Models;

    use Carbon\Carbon;
    use BbqData\Helpers\Fields;
    use BbqData\Contracts\Model;
    use BbqData\Helpers\Recurring;
    use Illuminate\Database\Eloquent\SoftDeletes;


    class Membership extends Model{

        use SoftDeletes;

        /**
         * Memberships table
         *
         * @var string
         */
        protected $table = 'memberships';

        //only guard the id
        protected $guarded = ['id'];


        /**
         * A membership belongs to a user
         *
         * @return User
         */
        public function user()
        {
            return $this->belongsTo('BbqData\Models\User');
        }

        /**
         * A membership has one product
         *
         * @return Product
         */
        public function product()
        {
            return $this->belongsTo( 'BbqData\Models\Product', 'product_id', 'ID' );   
        }

        /**
         * A membership has one payment
         *
         * @return Payment
         */
        public function payment()
        {
            return $this->belongsTo('BbqData\Models\Payment');
        }

         /**
         * A membership has many orders
         *
         * @return void
         */
        public function orders()
        {
            return $this->belongsToMany('BbqData\Models\Order', 'bbquality_membership_orders' );
        }


        /**
         * Save user fields 
         *
         * @param array $data
         * @return void
         */
        public function save_user_fields( $data )
        {
            //save the fields as user-meta:
            $fields = Fields::recurring();
            foreach( $fields as $name => $field ){
                update_user_meta( $this->user_id, $name, $data[ $name ] );
            }
            update_user_meta( $this->user_id, 'country', $data['country']);
        }
        

        /**
         * Return the price attribute for this membership
         *
         * @return float
         */
        public function getPriceAttribute()
        {
            //if there's no product, we have no idea:
            if( is_null( $this->product_id ) ){
                return 0;
            }   
            
            //get the price from the post meta: 
            $price = (float)\get_post_meta( $this->product_id, '_price', true );

            //if this is the first time we're ordering, we give a discount:
            if( is_null( $this->recurring_id ) ){
                $price = ( $price * 0.75 );
            }

            return $price;
        }


         /**
         * If this membership is new
         * If the membership is created on or after the last deliveryday then the membership is recent.
         *
         * @return boolean
         */
        public function getRecentAttribute()
        {
            $last_delivery = Recurring::lastDeliveryDay(); 
            return ($this->created_at >= $last_delivery);
        }


        /**
         * Return the customer reference
         *
         * @return string
         */
        public function getCustomerReferenceAttribute()
        {
            return 'customer_'.$this->user_id;
        }


        /**
         * Return the delivery date
         *
         * @return void
         */
        public function getDeliveryDayAttribute()
        {
            $value = $this->delivery_date;
            if( is_null( $value ) || $value == '' ){
                $value = Recurring::nextDeliveryDay(); //return default
            }

            return Carbon::parse( $value );
        }



        /**
         * Return the next delivery date
         *
         * @return Carbon instance
         */
        public function getNextDeliveryDateAttribute()
        {
            $next = Recurring::nextDeliveryDay();
            if( $next->format('Y-m-d') == Carbon::now()->format('Y-m-d') ){
                $next = $next->addMonth();
            }

            return $next;
        }

        /**
         * Return the following delivery date attribute
         *
         * @return void
         */
        public function getFollowingDeliveryDateAttribute()
        {
            $next = Recurring::nextMonthsDeliveryDay();
            return \date_i18n( 'j F', $next->timestamp );
        }

        /**
         * Return the name of the next month formatted
         *
         * @return string
         */
        public function getNextMonthAttribute()
        {
            $next = $this->next_delivery_date;
            return \date_i18n( 'F', $next->timestamp );
        }

        

        
        /**
         * Return the delivery options
         *
         * @return void
         */
        public function getDeliveryOptionsAttribute()
        {
            $next = $this->next_delivery_date->subDays(3);
            $response = [];
            
            for( $i = 1; $i <= 4; $i++ ){
                $next->addDays(1);
                $response[ $next->format('Y-m-d') ] = \date_i18n('l j F', $next->timestamp );
            }
    
            return $response;
        }


        /**
         * Return the first order of this membership
         *
         * @return Order
         */
        public function getFirstOrderAttribute()
        {
            return $this->payment->order;
        }

       

    }