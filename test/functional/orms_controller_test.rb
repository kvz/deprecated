require 'test_helper'

class OrmsControllerTest < ActionController::TestCase
  test "should get index" do
    get :index
    assert_response :success
    assert_not_nil assigns(:orms)
  end

  test "should get new" do
    get :new
    assert_response :success
  end

  test "should create orm" do
    assert_difference('Orm.count') do
      post :create, :orm => { }
    end

    assert_redirected_to orm_path(assigns(:orm))
  end

  test "should show orm" do
    get :show, :id => orms(:one).to_param
    assert_response :success
  end

  test "should get edit" do
    get :edit, :id => orms(:one).to_param
    assert_response :success
  end

  test "should update orm" do
    put :update, :id => orms(:one).to_param, :orm => { }
    assert_redirected_to orm_path(assigns(:orm))
  end

  test "should destroy orm" do
    assert_difference('Orm.count', -1) do
      delete :destroy, :id => orms(:one).to_param
    end

    assert_redirected_to orms_path
  end
end
