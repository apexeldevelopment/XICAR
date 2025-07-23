use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('language_merchant_membership_plans', function (Blueprint $table) {
            $table->id();
            $table->integer('merchant_membership_plan_id')->unsigned();
            $table->foreign('merchant_membership_plan_id')->references('id')->on('merchant_membership_plans')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->string('plan_title')->nullable();
            $table->text('description')->nullable();
            $table->string('locale')->default('en');
            $table->string('plan_name')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('language_merchant_membership_plans');
    }
};