<?php 

    namespace BbqData\Models;

    use Carbon\Carbon;
    use BbqData\Contracts\Model;

    class CouponCampaign extends Model
    {

        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'coupon_campaign';
        

        /**
         * Only the id is guarded
         *
         * @var array
         */
        protected $fillable = [ 'name','source' ];


        /**
         * A coupon can belong to many orders
         *
         * @return void
         */
        public function coupons()
        {
            return $this->hasMany( Coupon::class );
        }
    
    }
