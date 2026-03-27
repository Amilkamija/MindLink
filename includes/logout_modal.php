<!--
Author: Maksims Gerkis
Description: Logout modal
Verison:1
-->


<!--                        LOG OUT BUTTON PHP CODE                          -->

<!-- Modal Container for the Logout button-->
<div class =  "modal fade" id="logoutModal" tabindex="-1">

    <!-- Centers the modal on the screen  -->
    <div class   = " modal-dialog modal-dialog-centered">



        <!-- The Actual Content of the modal on the screen  -->
        <div class=  "modal-content text-center p-3">


            <!-- Text for the Logout button  -->
            <p>Are you sure you want to log out?</p>
            <!-- container for buttons  -->
            <div class    ="d-flex justify-content-center gap-2">
                <!-- Cancel Button to close it -->
                <button class ="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <!-- Actual Button To Logout  -->


                <a href  ="/pages/log_out.php" class="btn btn-dark">Log Out</a>
            </div>

        </div>

    </div>
</div>