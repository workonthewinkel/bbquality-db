<?php

    namespace BbqData\Models;

	use Carbon\Carbon;
    use BbqData\Contracts\Model;

    class Promotion extends Model{

    
        /**
         * Payment table
         *
         * @var string
         */
        protected $table = 'promotions';

        /**
         * Only shield ids from mass-filling
         * 
         * @var Array
         */
        protected $guarded = ['id'];

        /**
         * A promotion belongs to a product
         *
         * @return void
         */
        public function product()
        {
            return $this->belongsTo( Product::class, 'product_id', 'ID');
        }
        
        /**
         * A production may belong to a product variation
         *
         * @return void
         */
        public function variation()
        {
            return $this->hasOne( ProductVariation::class, 'variation_id', 'id');
        }

		
		/**
		 * Return the promotions for a certain threshold
		 *
		 * @param float $threshold
		 * @return Promotion
		 */
		public static function forThreshold( $queried_threshold )
		{
			$response = \collect([]);
			$results = static::current()->where('threshold', '<=', $queried_threshold )->ascending()->get();
			if( !$results->isEmpty() ){
				$results = $results->groupBy( 'threshold' );
		
				foreach( $results as $threshold => $promotions){
					if( $queried_threshold >= $threshold ){
						$response = $promotions;
					}
				}
			}

			return $response;
		}

		/**
		 * Return the start on attribute as a carbon instance
		 *
		 * @param datetime $value
		 * @return Carbon
		 */
		public function getStartOnAttribute( $value )
		{
			return \Carbon\Carbon::parse( $value );
		}

		/**
		 * Return the end_on attribute as a carbon instance
		 *
		 * @param datetime $value
		 * @return Carbon
		 */
		public function getEndOnAttribute( $value )
		{
			return \Carbon\Carbon::parse( $value );
		}

		/**
		 * Only return current promotions
		 *
		 * @param QueryBuilder $builder
		 * @return QueryBuilder
		 */
		public function scopeCurrent( $builder )
		{
			return $builder->where( 'start_on', '<=', Carbon::now() )->where( 'end_on', '>', Carbon::now() );
		}

		/**
		 * Return promotions in ascending order
		 *
		 * @param QueryBuilder $builder
		 * @return QueryBuilder
		 */
		public function scopeAscending( $builder )
		{
			return $builder->orderBy( 'threshold' );
		}
		
    }
