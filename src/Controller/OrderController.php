<?php


namespace App\Controller;


use App\Entity\Product;
use App\Entity\User;
use App\Store\ShoppingCart;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
  public function checkoutAction(Request $request, EntityManagerInterface $em){
    $products = $this->cart->getProducts();

    if($request->isMethod('POST')){
			$token = $request->get('stripeToken');

	    \Stripe\Stripe::setApiKey($this->getParameter('stripe_secret_key'));

	    /** @var User $user*/
	    $user = $this->getUser();
	    if (!$user->getStripeCustomerId()){
		    $customer = \Stripe\Customer::create([
			    'email' => $user->getEmail(),
			    'source' => $token
		    ]);
		    $user->setStripeCustomerId($customer->id);
		    $em->persist($user);
		    $em->flush();
	    } else {
		    $customer = \Stripe\Customer::retrieve($user->getStripeCustomerId());
		    $customer->source = $token;
		    $customer->save();
	    }

	    \Stripe\InvoiceItem::create([
		    'amount' => $this->cart->getTotal() * 100,
		    'currency' => 'usd',
		    'customer' => $user->getStripeCustomerId(),
		    'description' => '"First test charge!',
	    ]);
	    $invoice = \Stripe\Invoice::create([
		    'customer' => $user->getStripeCustomerId()
	    ]);
	    $invoice->pay();
	    
	    $this->cart->emptyCart();
	    $this->addFlash('success', 'Order Complete! Yay!');

	    return $this->redirectToRoute('homepage');
    }

    return $this->render('order/checkout.html.twig', array(
      'products' => $products,
      'cart' => $this->cart,
	    'stripe_key' => $this->getParameter('stripe_public_key')
    ));

  }
}