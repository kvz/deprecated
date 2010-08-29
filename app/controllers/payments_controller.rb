class PaymentsController < ApplicationController
  include ActiveMerchant::Billing

  def checkout
    setup_response = gateway.setup_purchase(5000,
      :ip                => remote_ip,
      :return_url        => url_for(:action => 'confirm', :id => params[:id], :only_path => false),
      :cancel_return_url => url_for(:action => 'show', :controller => 'orms', :id => params[:id], :only_path => false)
    )
    redirect_to gateway.redirect_url_for(setup_response.token)
  end

  def confirm
    redirect_to :action => 'show', :controller => 'orms', :id => params[:id] unless params[:token]

    details_response = gateway.details_for(params[:token])

    if !details_response.success?
      @message = details_response.message
      render :action => 'error'
      return
    end
    @address = details_response.address
  end

  def complete
    purchase = gateway.purchase(5000,
      :ip       => request.remote_ip,
      :payer_id => params[:payer_id],
      :token    => params[:token]
    )

    if !purchase.success?
      @message = purchase.message
      render :action => 'error'
      return
    else
      flash[:notice] = 'You\'ve payed for this ORM'
      redirect_to :action => 'show', :controller => 'orms', :id => params[:id]
    end
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
