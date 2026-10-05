package com.empress.twoway.ui;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.empress.twoway.R;
import com.empress.twoway.network.ApiClient;
import com.empress.twoway.util.SessionManager;

import org.json.JSONObject;

public class ProfileFragment extends Fragment {

    private SwipeRefreshLayout swipeRefresh;
    private TextView tvProfileKycStatus;
    private EditText etAddressLine, etCity, etState, etPincode, etPanNumber, etCryptoWallet;
    private Button btnSaveProfile;
    private ProgressBar pbProfile;

    private SessionManager sessionManager;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_profile, container, false);

        sessionManager = new SessionManager(requireContext());

        swipeRefresh = view.findViewById(R.id.swipeRefresh);
        tvProfileKycStatus = view.findViewById(R.id.tvProfileKycStatus);
        etAddressLine = view.findViewById(R.id.etAddressLine);
        etCity = view.findViewById(R.id.etCity);
        etState = view.findViewById(R.id.etState);
        etPincode = view.findViewById(R.id.etPincode);
        etPanNumber = view.findViewById(R.id.etPanNumber);
        etCryptoWallet = view.findViewById(R.id.etCryptoWallet);
        btnSaveProfile = view.findViewById(R.id.btnSaveProfile);
        pbProfile = view.findViewById(R.id.pbProfile);

        swipeRefresh.setOnRefreshListener(this::loadProfileData);

        btnSaveProfile.setOnClickListener(v -> saveProfileData());

        loadProfileData();

        return view;
    }

    private void loadProfileData() {
        swipeRefresh.setRefreshing(true);

        ApiClient.get("user/profile.php", sessionManager.getToken(), new ApiClient.ApiCallback() {
            @Override
            public void onSuccess(JSONObject response) {
                if (!isAdded()) return;
                swipeRefresh.setRefreshing(false);

                if (response.optBoolean("success")) {
                    JSONObject data = response.optJSONObject("data");
                    if (data != null) {
                        tvProfileKycStatus.setText("KYC Status: " + data.optString("kyc_status", "Pending"));
                        etAddressLine.setText(data.optString("address_line", ""));
                        etCity.setText(data.optString("city", ""));
                        etState.setText(data.optString("state", ""));
                        etPincode.setText(data.optString("pincode", ""));
                        etPanNumber.setText(data.optString("pan_number", ""));

                        String wallet = data.optString("crypto_wallet_address", data.optString("bep20_address", ""));
                        etCryptoWallet.setText(wallet);
                    }
                } else {
                    Toast.makeText(requireContext(), response.optString("message", "Failed to load profile"), Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onError(String errorMessage) {
                if (!isAdded()) return;
                swipeRefresh.setRefreshing(false);
                Toast.makeText(requireContext(), errorMessage, Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void saveProfileData() {
        String address = etAddressLine.getText().toString().trim();
        String city = etCity.getText().toString().trim();
        String state = etState.getText().toString().trim();
        String pincode = etPincode.getText().toString().trim();
        String pan = etPanNumber.getText().toString().trim();
        String cryptoWallet = etCryptoWallet.getText().toString().trim();

        if (address.isEmpty() || city.isEmpty() || state.isEmpty() || pincode.isEmpty() || cryptoWallet.isEmpty()) {
            Toast.makeText(requireContext(), "Address fields and crypto wallet address are required", Toast.LENGTH_SHORT).show();
            return;
        }

        btnSaveProfile.setEnabled(false);
        pbProfile.setVisibility(View.VISIBLE);

        try {
            JSONObject body = new JSONObject();
            body.put("address_line", address);
            body.put("city", city);
            body.put("state", state);
            body.put("pincode", pincode);
            body.put("pan_number", pan);
            body.put("crypto_wallet_address", cryptoWallet);
            body.put("bep20_address", cryptoWallet);
            body.put("wallet_network", "USDT (BEP20)");

            ApiClient.post("user/profile.php", body, sessionManager.getToken(), new ApiClient.ApiCallback() {
                @Override
                public void onSuccess(JSONObject response) {
                    if (!isAdded()) return;
                    btnSaveProfile.setEnabled(true);
                    pbProfile.setVisibility(View.GONE);

                    if (response.optBoolean("success")) {
                        Toast.makeText(requireContext(), response.optString("message", "Profile updated successfully!"), Toast.LENGTH_LONG).show();
                        loadProfileData();
                    } else {
                        Toast.makeText(requireContext(), response.optString("message", "Profile update failed"), Toast.LENGTH_LONG).show();
                    }
                }

                @Override
                public void onError(String errorMessage) {
                    if (!isAdded()) return;
                    btnSaveProfile.setEnabled(true);
                    pbProfile.setVisibility(View.GONE);
                    Toast.makeText(requireContext(), errorMessage, Toast.LENGTH_LONG).show();
                }
            });

        } catch (Exception e) {
            btnSaveProfile.setEnabled(true);
            pbProfile.setVisibility(View.GONE);
            Toast.makeText(requireContext(), "Error: " + e.getMessage(), Toast.LENGTH_SHORT).show();
        }
    }
}
