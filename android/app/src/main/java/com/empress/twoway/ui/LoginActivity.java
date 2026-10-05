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

public class LoginActivity extends AppCompatActivity {

    private EditText etMemberId, etPassword;
    private Button btnLogin;
    private ProgressBar progressBar;
    private TextView tvRegisterLink;
    private SessionManager sessionManager;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        sessionManager = new SessionManager(this);
        if (sessionManager.isLoggedIn()) {
            startActivity(new Intent(LoginActivity.this, MainActivity.class));
            finish();
            return;
        }

        setContentView(R.layout.activity_login);

        etMemberId = findViewById(R.id.etMemberId);
        etPassword = findViewById(R.id.etPassword);
        btnLogin = findViewById(R.id.btnLogin);
        progressBar = findViewById(R.id.progressBar);
        tvRegisterLink = findViewById(R.id.tvRegisterLink);

        btnLogin.setOnClickListener(v -> performLogin());

        tvRegisterLink.setOnClickListener(v -> {
            startActivity(new Intent(LoginActivity.this, RegisterActivity.class));
        });
    }

    private void performLogin() {
        String memberId = etMemberId.getText().toString().trim();
        String password = etPassword.getText().toString().trim();

        if (memberId.isEmpty() || password.isEmpty()) {
            Toast.makeText(this, "Please enter Member ID and Password", Toast.LENGTH_SHORT).show();
            return;
        }

        btnLogin.setEnabled(false);
        progressBar.setVisibility(View.VISIBLE);

        try {
            JSONObject body = new JSONObject();
            body.put("user_type", "member");
            body.put("member_id", memberId);
            body.put("password", password);

            ApiClient.post("auth/login.php", body, null, new ApiClient.ApiCallback() {
                @Override
                public void onSuccess(JSONObject response) {
                    btnLogin.setEnabled(true);
                    progressBar.setVisibility(View.GONE);

                    if (response.optBoolean("success")) {
                        String token = response.optString("token");
                        JSONObject userObj = response.optJSONObject("user");
                        String mId = userObj != null ? userObj.optString("member_id") : memberId;
                        String name = userObj != null ? userObj.optString("name") : "";
                        String email = userObj != null ? userObj.optString("email") : "";

                        sessionManager.createLoginSession(token, mId, name, email);

                        Toast.makeText(LoginActivity.this, "Login Successful!", Toast.LENGTH_SHORT).show();
                        startActivity(new Intent(LoginActivity.this, MainActivity.class));
                        finish();
                    } else {
                        String msg = response.optString("message", "Login failed");
                        Toast.makeText(LoginActivity.this, msg, Toast.LENGTH_LONG).show();
                    }
                }

                @Override
                public void onError(String errorMessage) {
                    btnLogin.setEnabled(true);
                    progressBar.setVisibility(View.GONE);
                    Toast.makeText(LoginActivity.this, errorMessage, Toast.LENGTH_LONG).show();
                }
            });

        } catch (Exception e) {
            btnLogin.setEnabled(true);
            progressBar.setVisibility(View.GONE);
            Toast.makeText(this, "Error forming login request: " + e.getMessage(), Toast.LENGTH_SHORT).show();
        }
    }
}
