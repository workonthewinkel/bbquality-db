<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;

    class Index extends Model
    {
        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'indexes';

        /**
         * Only the id and order_id are guarded
         *
         * @var array
         */
        protected $guarded = ['id'];

         /**
         * Constant with allowed states
         *
         * @var array
         */
        const POST_TYPES = [
            'product' => 0,
            'recipe' => 1,
            'faq' => 2,
            'post' => 3,
        ];

        /**
         * An order has a payment:
         *
         * @return void
         */
        public function filter()
        {
            return $this->belongsTo('BbqData\Models\Filter');
        }

        /**
         * Return the post-type as a string
         *
         * @param int $value
         * @return string
         */
        public function getPostTypeAttribute( $value )
        {
            return static::post_type( $value );
        }

        /**
         * Return the correct (int) post_type for this relation    
         *
         * @param  $string
         * @return void
         */
        public static function post_type( $type ) 
        {
            return self::POST_TYPES[ $type ] ?? null;    
        }
    }
