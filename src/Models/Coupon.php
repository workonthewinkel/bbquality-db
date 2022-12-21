<?php 

    namespace BbqData\Models;

    use Carbon\Carbon;
    use BbqData\Helpers\Price;
    use BbqData\Contracts\Model;

    class Coupon extends Model
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
        protected $table = 'coupons';
        protected $pivot = 'bbquality_coupon_order';
        

        /**
         * Only the id and order_id are guarded
         *
         * @var array
         */
        protected $fillable = [
            'code',
            'type',
            'amount',
            'minimal_amount',
            'valid_from',
            'valid_through',
            'usage',
            'used',
            'is_gift_certificate',
            'free_shipping',
            'term_id',
            'coupon_campaign_id'
        ];


        const STATES = [
            'bought' => 1,
            'redeemed' => 2,
            'remaining' => 3,
            'earned' => 4,
        ];

        /**
         * A coupon can belong to many orders
         *
         * @return void
         */
        public function orders()
        {
            return $this->belongsToMany('BbqData\Models\Order', $this->pivot )
                        ->withPivot('status', 'amount', 'created_at', 'updated_at')
                        ->withTimestamps();
        }

        
        /**
         * A coupon can belong to many carts
         *
         * @return void
         */
        public function carts()
        {
            return $this->belongsToMany('BbqData\Models\Cart', $this->pivot )
                        ->withPivot('status', 'amount', 'created_at', 'updated_at')
                        ->withTimestamps();
        }

        /**
         * Return a coupons users
         *
         * @return void
         */
        public function users()
        {
            return $this->belongsToMany('BbqData\Models\User', 'bbquality_coupon_users' )
                        ->withPivot('ip');
        }


        /**
         * Return a specific state
         *
         * @param string $key
         * @return int
         */
        public static function state( $key )
        {
            return (int) static::STATES[ $key ] ?? null;
        }


        /**
         * Return all ids for certificates
         *
         * @return Array
         */
        public static function getCertificateIds()
        {
            //get the gift certificate ids:
            $ids = static::table('postmeta')->select('post_id')
                    ->where([
                        'meta_key'      => '_is_gift_certificate',
                        'meta_value'    => 'true'
                    ])->get()->pluck('post_id')->all();
        
            return $ids;
        }


        /**
         * Render the amount this coupon is worth
         *
         * @return void
         */
        public function getDisplayAmountAttribute()
        {
            return Price::format( $this->amount );
        }

        /**
         * Get the valid from attribute, as a Carbon instance
         *
         * @return Carbon
         */
        public function getValidFromAttribute( $value )
        {
            return Carbon::parse( $value );
        }

        /**
         * Get the valid through attribute, as a Carbon instance
         *
         * @return Carbon
         */
        public function getValidUntillAttribute()
        {
            return Carbon::parse( $this->valid_through );
        }


        /**
         * Return the readable version of the max valid through 
         *
         * @return string
         */
        public function getReadableValidDateAttribute()
        {
            return \date_i18n( 'j F Y', strtotime($this->valid_through) );
        }

        /**
         * Return the readable version of the min valid from
         *
         * @return string
         */
        public function getReadableValidFromAttribute()
        {
            return \date_i18n( 'j F Y', strtotime($this->valid_from ) );
        }
    

        /**
         * Return all bought coupons
         *
         * @return void
         */
        public function scopeBought( $query )
        {
            return $query->whereHas( 'orders', function( $coupon ) {
                $coupon->where( $this->pivot.'.status', static::STATES['bought'] );
            });
        }


        /**
         * Return all redeemed coupons
         *
         * @return void
         */
        public function scopeRedeemed( $query )
        {
            return $query->whereHas( 'orders', function( $coupon ) {
                $coupon->where( $this->pivot.'.status', static::STATES['redeemed'] );
            });
        }

        /**
         * Return all redeemed coupons
         *
         * @return void
         */
        public function scopeRemaining( $query )
        {
            return $query->whereHas( 'orders', function( $coupon ) {
                $coupon->where( $this->pivot.'.status', static::STATES['remaining'] );
            });
        }


        /**
         * Return all earned coupons - coupons earned during campaign 
         *
         * @return void
         */
        public function scopeEarned( $query )
        {
            return $query->whereHas( 'orders', function( $coupon ) {
                $coupon->where( $this->pivot.'.status', static::STATES['earned'] );
            });
        }

        /**
         * Return all redeemed coupons
         *
         * @return void
         */
        public function scopePrintable( $query )
        {
            $coupons = $query->whereHas( 'orders', function( $coupon ) {
                $states = [ static::STATES['bought'], static::STATES['remaining'], static::STATES['earned']  ];
                $coupon->whereIn($this->pivot.'.status', $states );
            });

            $coupons = $coupons->get();
            $response = [];
            foreach( $coupons as $coupon ){
                if( $coupon->status !== static::STATES['redeemed'] ){
                    $response[] = $coupon;
                }
            }

            return \collect( $response );
        }
    
    }