<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;

    class Review extends Model
    {
        
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


    }
