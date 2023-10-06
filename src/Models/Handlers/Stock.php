<?php

    namespace BbqData\Models\Handlers;

    use Cuisine\Utilities\Logger;
    use BbqData\Models\Notification;
    use Illuminate\Database\Capsule\Manager as Capsule;

    class Stock{

        /**
         * Modus operandi
         *
         * @var string
         */
        protected $mode;

        /**
         * Object we're manipulating
         *
         * @var Product / ProductVariation
         */
        protected $object;

        
        /**
         * Constructor
         *
         * @param String $mode
         * @param Product / ProductVariation $object
         */
        public function __construct( $mode, $object )
        {
            $this->mode = $mode;
            $this->object = $object;
        }

       
        /**
         * Reduce the stock amount
         *
         * @param int $amount
         * @return void
         */
        public function reduce( $amount )
        {
            //account for dummy mode
            if( $this->mode == 'dummy' ){
                $this->createWarning( 0 );
                return;
            }

            if( $this->mode !== 'product' ){
                $stock = ( $this->object->stock - absint( $amount ) );
                $this->object->stock = $stock;
                $this->object->save();
            }else{

                //run a raw db query, to bypass wordPress caching.
                $query = Capsule::table( 'bbquality_postmeta' )
                    ->where('post_id', $this->object->ID )
                    ->where('meta_key', '_stock');

                $meta = $query->select('*')->first();
                $old_amount = (int)$meta->meta_value ?? 0;                
                $stock = ( $old_amount - absint( $amount ) );
                $query->update(['meta_value' => $stock ]);
            }

            //flush object cache:
            if( $stock <= 5 ){
                global $wp_object_cache;
                $wp_object_cache->flush();

                //purge page cache as well:
                if( function_exists( 'spinupwp_purge_site' ) ){
                    spinupwp_purge_site();
                }
                //Logger::error( "Object Cache flushed after stock reduction." );
            }
            
            $this->createWarning( $stock );
        }


        /**
         * Add the stock amount
         *
         * @param int $amount
         * @return void
         */
        public function add( $amount )
        {
            //account for dummy mode
            if( $this->mode == 'dummy' ){
                return;
            }

            if( $this->mode !== 'product' ){
                $this->object->stock = ( $this->object->stock + absint( $amount ) );
                $this->object->save();
            }else{
                $stock = \get_post_meta( $this->object->ID, '_stock', true );
                $stock = ( $stock + absint( $amount ) );
                \update_post_meta( $this->object->ID, '_stock', $stock );
            }
        }


        /**
         * Set the stock amount
         *
         * @param int $amount
         * @return void
         */
        public function set( $amount )
        {
            //account for dummy mode
            if( $this->mode == 'dummy' ){
                return;
            }

            if( $this->mode !== 'product' ){
                $this->object->stock = absint( $amount );
                $this->object->save();
            }else{
                \update_post_meta( $this->object->ID, '_stock', absint( $amount ) );
            }

            $this->createWarning( $amount );
        }


        /**
         * Create stock warnings
         *
         * @param Int $stock
         * @return void
         */
        public function createWarning( $stock )
        {
            $product_id = ( $this->mode !== 'product' ? $this->object->product_id : $this->object->ID );
            $object_id = ( $this->mode !== 'product' ? $this->object->id : $this->object->ID );

            //overwrite the product for the dummy:
            if( $this->mode == 'dummy' ){
                $object_id = $this->object->product_id;
            }

            $threshold = \absint( \get_post_meta( $product_id, '_stock_threshold', true ) );

            if( absint( $stock ) <= absint( $threshold ) ){

                //check if one exists already:
                $stock_warning = Notification::where([
                    'object_id' => $object_id, 
                    'dismissed' => false,
                    'type' => 'stock_warning'
                ])->first();

                
                if( is_null( $stock_warning ) ){
                    $stock_warning = new Notification();
                }

                $title = $this->object->title ?? $this->object->description;
                $data = [
                    'type' => $this->mode,
                    'product_id' => $product_id,
                    'level' => 'danger'
                ];
                
                $state = ( $stock > 0 ? 'bijna ' : '' ).'uitverkocht';
                if( $stock > 0 ){
                    $data['level'] = 'warning';
                    $state .= ' (nog '.$stock.' op voorraad)';
                }

                if( $this->mode == 'dummy' ){
                    $state = 'uitverkocht of niet beschikbaar.';
                }

                $stock_warning->fill([
                    'message' => $title.' is '.$state,
                    'data' => json_encode( $data ),
                    'object_id' => $object_id,
                ])->save();
            }
        }


        /**
         * Get the stock of this object
         *
         * @return void
         */
        public function get()
        {
            if( $this->mode !== 'product' ){
                return (int)$this->object->stock;
            }else{
                return (int)\get_post_meta( $this->object->ID, '_stock', true );
            }
        }

        /**
         * Return the stock object that was manipulated
         *
         * @return void
         */
        public function object()
        {
            return $this->object;
        }


        /**
         * Check 
         *
         * @return void
         */
        public function check( $row )
        {
            $remaining = $this->get();

            //if we're out of stock:            
            if( $remaining < $row->quantity ){

                $message = 'We hebben helaas %s stuks van %s op voorraad';
                
                //if the products are in your cart, add onto that message: 
                if( $row->quantity == $remaining ){
                    $message .= ' en deze zitten al in je winkelwagentje.';
                }
                
                //return the message array
                $msg = [
                    'error' => 'stock',
                    'message' => sprintf( 
                                    $message,
                                    $remaining,
                                    $this->object->title
                    ),
                    'remaining_stock' => $remaining
                ];

                return $msg;
            }

            return true;
        }


        /**
         * Returns the total stock for a product
         *
         * @return void
         */
        public function total()
        {
            $variations = $this->object->variations;
            if( is_null( $variations ) || $variations->isEmpty() ){
                return $this->get();
            
            }else{
                $total = 0;
                foreach( $variations as $variation ){
                    $total += $variation->stock()->get();
                }
                return $total;
            } 
        }
    }
