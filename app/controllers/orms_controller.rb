class OrmsController < ApplicationController
  include ActiveMerchant::Billing
  skip_before_filter :verify_authenticity_token

  def checkout
    @orm = Orm.new()
    setup_response = gateway.setup_purchase(@orm.price,
      :ip                => remote_ip,
      :return_url        => url_for(:action => 'complete', :id => params[:id], :only_path => false),
      :cancel_return_url => url_for(:action => 'show', :id => params[:id], :only_path => false)
    )
    redirect_to gateway.redirect_url_for(setup_response.token)
  end

  def complete
    details_response = gateway.details_for(params[:token])
    if !details_response.success?
      flash[:error] = details_response.message
      redirect_to :action => 'show', :id => params[:id]
    end
    @address = details_response.address

    @orm = Orm.find(params[:id])
    purchase = gateway.purchase(@orm.price,
      :ip       => remote_ip,
      :token    => params[:token],
      :payer_id => params[:PayerID]
      #:payer_id => params[:payer_id],
    )

    @payment = Payment.new(@address)
    @payment.orm_id = params[:id]
    @payment.token = params[:token]
    @payment.payer_id = params[:PayerID]
    @payment.price = @@price
    @payment.ip = remote_ip
    @payment.billing_mode = ActiveMerchant::Billing::Base.mode

    if !purchase.success?
      @payment.status = purchase.message
      flash[:error] = purchase.message
    else
      @payment.status = 'ok'
      flash[:notice] = 'Thanks ' + @address['name'] + ' for purchasing this ORM!'
    end

    if !@payment.save
      flash[:error] = 'Unable to store your payment. Be sure to contact us!'
    end
    if !@orm.build_graph
      flash[:error] = 'Unable to regenerate ORM graph. Be sure to contact us!'
    end

    redirect_to :action => 'show', :id => params[:id]
  end


  # GET /posts
  # GET /posts.xml
  def index
    @orms = Orm.all

    respond_to do |format|
      format.html # index.html.erb
      format.xml  { render :xml => @orms }
    end
  end

  # GET /orms/1
  # GET /orms/1.xml
  def show
    @orm = Orm.find(params[:id])
    # @todo: don't build_graph on show when done testing
    @orm.build_graph
    respond_to do |format|
      format.html # show.html.erb
      format.xml  { render :xml => @orm }
    end
  end

  # GET /posts/new
  # GET /posts/new.xml
  def new
    @orm = Orm.new

    respond_to do |format|
      format.html # new.html.erb
      format.xml  { render :xml => @orm }
    end
  end
  
  # GET /orms/1/edit
  def edit
    @orm = Orm.find(params[:id])
  end

  # POST /orms
  # POST /orms.xml
  def create
    # Convert the multipart source to plain text source field
    # for use with cat server.php | curl -F orm[source]=@- http://ormify.com/orms/
    if File.file?(params[:orm][:source])
      data = ''
      params[:orm][:source].each_line do |line|
        data += line
      end
      params[:orm][:source] = data
      curled = true
    end
    params[:orm][:ip] = remote_ip
    @orm = Orm.new(params[:orm])
    respond_to do |format|
      if @orm.build_graph()
        flash[:notice] = 'Orm was successfully created.'
        format.html { redirect_to(@orm) }
        format.xml  { render :xml => @orm, :status => :created, :location => @orm }
        format.text
      else
        format.html { render :action => "new" }
        format.xml  { render :xml => @orm.errors, :status => :unprocessable_entity }
        format.text { render :text => @orm.errors, :status => :unprocessable_entity }
      end
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
