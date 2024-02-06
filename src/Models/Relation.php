<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;

    class Relation extends Model
    {

        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'relations';


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
            'related' => 0,
            'upsells' => 1,
            'bundled' => 2,
            'bought_together' => 3,
        ];


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
         * A relation has a post    
         *
         * @return Post
         */
        public function posts()
        {
            return $this->hasOne( Post::class, 'ID', 'related_post_id' );
        }


        /**
         * A relation has an origin
         *
         * @return Post
         */
        public function origin()
        {
            return $this->hasOne( Post::class, 'ID', 'post_id' );
        }


        /**
         * Variation
         *
         * @return ProductVariation
         */
        public function variation()
        {
            return $this->hasOne( ProductVariation::class, 'id', 'variation_id' );
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
         * Return all related relationships
         *
         * @return QueryBuilder
         */
        public function scopeRelated( $query )
        {
            return $query->where( 'context', static::context( 'related' ) );
        }


        /**
         * Return all related upsells
         * 
         * @return QueryBuilder
         */
        public function scopeUpsells( $query )
        {
            return $query->where( 'context', static::context( 'upsells') );
        }


        /**
         * Return all related upsells
         * 
         * @return QueryBuilder
         */
        public function scopeBundled( $query )
        {
            return $query->where( 'context', static::context( 'bundled') );
        }


        /**
         * Return all related products
         *
         * @return QueryBuilder
         */
        public function scopeProducts( $query )
        {
            return $query->where( 'post_type', static::post_type( 'product' ) );
        }


        /**
         * Return all related recipes
         *
         * @return QueryBuilder
         */
        public function scopeRecipes( $query )
        {
            return $query->where( 'post_type', static::post_type( 'recipes' ) );
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


        /**
         * Return which contexts should also save a bidirectional relationship
         *
         * @return array
         */
        public static function bidirectional_contexts()
        {
            return ['related', 'bought_together'];
        }
    }
