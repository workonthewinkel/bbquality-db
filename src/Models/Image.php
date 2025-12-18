<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;
	use BbqData\Models\Casts\Json;
	use Illuminate\Support\Collection;

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
         * Cast the object field as a json
         *
         * @var array
         */
        protected $casts = [
            'sizes' => Json::class,
        ];

        /**
         * Constant with allowed contexts
         *
         * @var array
         */
        const CONTEXTS = ['featured', 'prepared', 'slider', 'content'];
		const SIZES = ['thumbnail', 'block', 'large', 'full'];


        /**
         * An image belongs to a post    
         *
         * @return Post
         */
        public function post()
        {
            return $this->hasOne( Post::class, 'ID', 'post_id' );
        }


		/**
		 * Return the thumbnail of this image
		 *
		 * @return string
		 */
		public function getThumbnailAttribute(): string {
			return $this->sizes['thumbnail'] ?? '';
		}

		/**
		 * Return the block-size of this image,
		 * defaults to full
		 *
		 * @return string
		 */
		public function getBlockAttribute(): string {
			$block = $this->sizes['block'] ?? '';
			if( $block !== '' ){
				return $block;
			}

			return $this->full;
		}

		/**
		 * Return the large image, defaults to full
		 *
		 * @return string
		 */
		public function getLargeAttribute(): string {
			
			$large = $this->sizes['large'] ?? '';
			if( $large !== '' ){
				return $large;
			}

			$this->full;
		}

		/**
		 * Return the full image.
		 *
		 * @return string
		 */
		public function getFullAttribute(): string {
			return $this->sizes['full'] ?? '';
		}


		/**
		 * Get Either the Prepared or Featured image within a clause
		 *
		 * @param array $clauses
		 * @return Image|null
		 */
		public function preparedOrFeatured( array $clauses ): ?Image {

			$result = $this->prepared()->where( $clauses )->first();
			if( !is_null( $result ) ){
				return $result;
			}

			return $this->featured()->where( $clauses )->first();
		}

		/**
		 * Return the (single) featured image.
		 * defaults to null
		 *
		 * @return Image|null
		 */
		public function scopeFeatured( $query ) {
			return $query->where('context', 'featured');
		}

		/**
		 * Return the (single) prepared image.
		 * defaults to featured.
		 *
		 * @return Image|null
		 */
		public function scopePrepared( $query ) {
			return $query->where('context', 'prepared');
		}

		/**
		 * Return all slider images as a laravel collection
		 *
		 * @return Collection
		 */
		public function scopeSlider( $query ) {
			return $query->where('context', 'slider');
		}

		/**
		 * Return all content images as a laravel collection
		 *
		 * @return Collection
		 */
		public function scopeContent( $query ) {
			return $query->where('context', 'content');
		}

		
    }
