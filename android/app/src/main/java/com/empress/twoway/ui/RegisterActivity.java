package com.empress.twoway.ui;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.empress.twoway.R;
import com.empress.twoway.network.ApiClient;
import com.empress.twoway.util.SessionManager;

import org.json.JSONObject;

public class RegisterActivity extends AppCompatActivity {

    private EditText etSponsorId, etEpinCode, etFullName, etEmail, etPhone, etPassword;
    private Button btnRegister;
    private ProgressBar progressBar;
    private TextView tvLoginLink;
    private SessionManager sessionManager;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_register);

        sessionManager = new SessionManager(this);

        etSponsorId = findViewById(R.id.etSponsorId);
        etEpinCode = findViewById(R.id.etEpinCode);
        etFullName = findViewById(R.id.etFullName);
        etEmail = findViewById(R.id.etEmail);
        etPhone = findViewById(R.id.etPhone);
        etPassword = findViewById(R.id.etPassword);
        btnRegister = findViewById(R.id.btnRegister);
        progressBar = findViewById(R.id.progressBar);
        tvLoginLink = findViewById(R.id.tvLoginLink);

        btnRegister.setOnClickListener(v -> performRegistration());

        tvLoginLink.setOnClickListener(v -> finish());
    }

    private void performRegistration() {
        String sponsorId = etSponsorId.getText().toString().trim();
        String epinCode = etEpinCode.getText().toString().trim();
        String name = etFullName.getText().toString().trim();
        String email = etEmail.getText().toString().trim();
        String phone = etPhone.getText().toString().trim();
        String password = etPassword.getText().toString().trim();

        if (epinCode.isEmpty() || name.isEmpty() || email.isEmpty() || password.isEmpty()) {
            Toast.makeText(this, "Please fill in all required fields (ePIN, Name, Email, Password)", Toast.LENGTH_SHORT).show();
            return;
        }

        btnRegister.setEnabled(false);
        progressBar.setVisibility(View.VISIBLE);

        try {
            JSONObject body = new JSONObject();
            body.put("sponsor_id", sponsorId.isEmpty() ? "EMP100000" : sponsorId);
            body.put("epin_code", epinCode);
            body.put("name", name);
            body.put("email", email);
            body.put("phone", phone);
            body.put("password", password);

            ApiClient.post("auth/register.php", body, null, new ApiClient.ApiCallback() {
                @Override
                public void onSuccess(JSONObject response) {
                    btnRegister.setEnabled(true);
                    progressBar.setVisibility(View.GONE);

                    if (response.optBoolean("success")) {
                        String memberId = response.optString("member_id");
                        String token = response.optString("token");

                        sessionManager.createLoginSession(token, memberId, name, email);

                        Toast.makeText(RegisterActivity.this, "Position Activated! Member ID: " + memberId, Toast.LENGTH_LONG).show();
                        startActivity(new Intent(RegisterActivity.this, MainActivity.class));
                        finishAffinity();
                    } else {
                        String msg = response.optString("message", "Registration failed");
                        Toast.makeText(RegisterActivity.this, msg, Toast.LENGTH_LONG).show();
                    }
                }

                @Override
                public void onError(String errorMessage) {
                    btnRegister.setEnabled(true);
                    progressBar.setVisibility(View.GONE);
                    Toast.makeText(RegisterActivity.this, errorMessage, Toast.LENGTH_LONG).show();
                }
            });

        } catch (Exception e) {
            btnRegister.setEnabled(true);
            progressBar.setVisibility(View.GONE);
            Toast.makeText(this, "Error forming registration request: " + e.getMessage(), Toast.LENGTH_SHORT).show();
        }
    }
}
