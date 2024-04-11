<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;

    class Image extends Model
    {

        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'images';


        /**
         * Only the id is guarded
         *
         * @var array
         */
        protected $guarded = ['id'];


        /**
         * Constant with allowed states
         *
         * @var array
         */
        const CONTEXTS = [
            'featured' => 0,
            'slider' => 1,
            'column' => 2,
            'content' => 3,
        ];


        /**
         * A relation has a post    
         *
         * @return Post
         */
        public function post()
        {
            return $this->hasOne( Post::class, 'ID', 'related_post_id' );
        }


        /**
         * Return the context as a string   
         *
         * @param int $value
         * @return string
         */
        public function getContextAttribute( $value )
        {
            return static::context( $value );
        }


        /**
         * Return all featured images
         *
         * @return QueryBuilder
         */
        public function scopeFeatured( $query )
        {
            return $query->where( 'context', static::context( 'featured' ) );
        }


        /**
         * Return all slider images
         * 
         * @return QueryBuilder
         */
        public function scopeSlider( $query )
        {
            return $query->where( 'context', static::context( 'slider') );
        }


        /**
         * Return all images from columns
         * 
         * @return QueryBuilder
         */
        public function scopeColumn( $query )
        {
            return $query->where( 'context', static::context( 'column') );
        }


        /**
         * Return all images from a posts content
         *
         * @param QueryBuilder $query
         * @return QueryBuilder
         */
        public function scopeContent( $query )
        {
            return $query->where( 'context', static::context( 'content' ) );
        }

        /**
         * Return a context for this relationship
         *
         * @param string $context
         * @return int
         */
        public static function context( $context )
        {
            return self::CONTEXTS[ $context ] ?? null;    
        }
    }
