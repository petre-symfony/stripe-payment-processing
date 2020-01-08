<?php
namespace App\Stripe;


use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class StripeClient {
	private $em;

	public function __construct(string $stripeSecretKey,EntityManagerInterface $em) {
		\Stripe\Stripe::setApiKey($stripeSecretKey);
		$this->em = $em;
	}

	public function createCustomer(User $user, $paymentToken){
		$customer = \Stripe\Customer::create([
			'email' => $user->getEmail(),
			'source' => $paymentToken
		]);

		$user->setStripeCustomerId($customer->id);
		$this->em->persist($user);
		$this->em->flush();

		return $customer;
	}

	public function updateCustomerCard(User $user, $paymentToken){
		$customer = \Stripe\Customer::retrieve($user->getStripeCustomerId());
		$customer->source = $paymentToken;
		$customer->save();
	}

	public function createInvoiceItem($amount, User $user, $description){
		return \Stripe\InvoiceItem::create([
			'amount' => $amount,
			'currency' => 'usd',
			'customer' => $user->getStripeCustomerId(),
			'description' => $description
		]);
	}

	public function createInvoice(User $user, $payImmediately=true){
		$invoice = \Stripe\Invoice::create([
			'customer' => $user->getStripeCustomerId()
		]);

		if($payImmediately) {
			$invoice->pay();
		}

		return $invoice;
	}
}