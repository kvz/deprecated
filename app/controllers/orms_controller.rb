class OrmsController < ApplicationController
  # GET /orms/1
  # GET /orms/1.xml
  def show
    @orm = Orm.find(params[:id])

    respond_to do |format|
      format.html # show.html.erb
      format.xml  { render :xml => @orm }
      format.svg  { render :svg => @orm }
      format.png  { render :png => @orm }
    end
  end
  
  # GET /orms/1/edit
  def edit
    @orm = Orm.find(params[:id])
  end

  # POST /orms
  # POST /orms.xml
  def create
    @orm = Orm.new(params[:orm])

    respond_to do |format|
      if @orm.save
        flash[:notice] = 'Orm was successfully created.'
        format.html { redirect_to(@orm) }
        format.xml  { render :xml => @orm, :status => :created, :location => @orm }
      else
        format.html { render :action => "new" }
        format.xml  { render :xml => @orm.errors, :status => :unprocessable_entity }
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
