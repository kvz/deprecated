class PaymentsController < ApplicationController
  include ActiveMerchant::Billing

  def index
  end

  def checkout
    setup_response = gateway.setup_purchase(5000,
      :ip                => remote_ip,
      :return_url        => url_for(:action => 'confirm', :only_path => false),
      :cancel_return_url => url_for(:action => 'index', :only_path => false)
    )
    redirect_to gateway.redirect_url_for(setup_response.token)
  end

  def confirm
  end

  def complete
  end
  
  private
  def gateway
    @gateway ||= PaypalExpressGateway.new(
      :login => 'kvz_1283090817_biz_api1.php.net',
      :password => '1283090824',
      :signature => 'A9umzV03tO-84Sc8dF5JkGDvC67MAlbshaqX36YkXBvIaWcGG9LVIri5'
    )
  end
end
