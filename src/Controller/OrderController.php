<?php


namespace App\Controller;


use App\Entity\Product;
use App\Store\ShoppingCart;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController {
  /**
   * @var ShoppingCart
   */
  private $cart;

  public function __construct(ShoppingCart $cart){
    $this->cart = $cart;
  }

  /**
   * @Route("/cart/product/{slug}", name="order_add_product_to_cart", methods={"POST"})
   */
  public function addProductToCartAction(Product $product){
    $this->cart->addProduct($product);

    $this->addFlash('success', 'Product added!');

    return $this->redirectToRoute('order_checkout');
  }

  /**
   * @Route("/checkout", name="order_checkout")
   * @IsGranted("ROLE_USER")
   */
  public function checkoutAction(){
    $products = $this->cart->getProducts();

    return $this->render('order/checkout.html.twig', array(
      'products' => $products,
      'cart' => $this->cart
    ));

  }
}