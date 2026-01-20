<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;

    class Review extends Model
    {

        protected $table = 'reviews';
        
		/**
         * Only the id is guarded
         *
         * @var array
         */
        protected $guarded = ['id'];


        /**
         * A Review belongs to a post.
         *
         * @return void
         */
        public function post()
        {
            return $this->belongsTo( Post::class, 'post_id', 'ID' );
        }

        /**
         * A Review belongs to a user.
         *
         * @return void
         */
        public function user()
        {
            return $this->belongsTo( User::class, 'user_id', 'ID' );
        }


		/**
         * And sometimes a Review belongs to an order.
         *
         * @return void
         */
        public function order()
        {
            return $this->belongsTo( Order::class );
        }

		/**
		 * Only show valid reviews
		 *
		 * @param Builder $builder
		 * @return Builder
		 */
		public function scopeValid( $builder )
		{
			return $builder->where( 'valid', true );
		}


		/**
		 * Return the reviewers name
		 *
		 * @return string
		 */
		public function getReviewerNameAttribute()
		{
            if ( !is_null( $this->name ) ){
                return $this->name;
            }else if( !is_null( $this->user_id ) ){
				return $this->user->display_name;
			}else if( !is_null( $this->order_id ) ){
				return $this->order->customer->name;
			}

			return '';
		}

    }
