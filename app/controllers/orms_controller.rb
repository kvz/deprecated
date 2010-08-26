class OrmsController < ApplicationController
  skip_before_filter :verify_authenticity_token
  
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
    @parsed = @orm.parse_source
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
    params[:orm][:ip] = request.env["HTTP_X_FORWARDED_FOR"]
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

  # PUT /orms/1
  # PUT /orms/1.xml
  def update
    @orm = Orm.find(params[:id])

    respond_to do |format|
      if @orm.update_attributes(params[:orm])
        flash[:notice] = 'Orm was successfully updated.'
        format.html { redirect_to(@orm) }
        format.xml  { head :ok }
      else
        format.html { render :action => "edit" }
        format.xml  { render :xml => @orm.errors, :status => :unprocessable_entity }
      end
    end
  end
end
