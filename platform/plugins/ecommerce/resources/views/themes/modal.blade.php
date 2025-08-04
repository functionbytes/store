
<div id="shopping-modal" class="shopping-modal modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <!-- Encabezado del Modal -->
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel">Producto añadido al carrito</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Cuerpo del Modal -->
            <div class="modal-body">
                <!-- Grid de Bootstrap para el layout -->
                <div class="row align-items-center product-details">

                    <!-- Columna de Detalles del Producto -->
                    <div class="col-lg-12">
                        <h3 class="product-title">{{ $cartItem['name'] }}</h3>
                        <p class="text-muted">Ref: {{ $cartItem['options']['sku'] }}</p>

                            <div class="border-top mt-3 pt-3">
                                <div class="d-flex justify-content-between">
                                    <span>Precio Unitario:</span>
                                    <span class="font-weight-bold">{{ $cartItem['price'] }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Cantidad:</span>
                                    <span class="font-weight-bold">{{ $cartItem['qty'] }}</span>
                                </div>

                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top ">
                                <span class="h5 mb-0">Subtotal:</span>
                                <span class="h4 mb-0 font-weight-bold text-danger">{{ $cartItem['subtotal'] }}</span>
                            </div>

                    </div>



                    <!-- Columna de Acciones -->
                    <div class="col-lg-12  border-top mt-3 pt-3">
                        <a href="{{ $checkout }}" class="btn btn-primary btn-block py-2 mb-1 w-100">
                            Realizar Pedido
                        </a>
                        <a href="/" data-dismiss="modal" class="btn btn-secondary btn-secundary py-2  w-100" id="add-more-btn">
                            Seguir Comprando
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

