package com.empress.twoway.ui;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.empress.twoway.R;
import com.empress.twoway.adapters.RebirthAdapter;
import com.empress.twoway.models.RebirthNode;
import com.empress.twoway.network.ApiClient;
import com.empress.twoway.util.SessionManager;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.List;

public class RebirthsFragment extends Fragment {

    private SwipeRefreshLayout swipeRefresh;
    private TextView tvTotalRebirthsEarned;
    private RecyclerView rvRebirthNodes;

    private SessionManager sessionManager;
    private final List<RebirthNode> rebirthList = new ArrayList<>();
    private RebirthAdapter adapter;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_rebirths, container, false);

        sessionManager = new SessionManager(requireContext());

        swipeRefresh = view.findViewById(R.id.swipeRefresh);
        tvTotalRebirthsEarned = view.findViewById(R.id.tvTotalRebirthsEarned);
        rvRebirthNodes = view.findViewById(R.id.rvRebirthNodes);

        rvRebirthNodes.setLayoutManager(new LinearLayoutManager(requireContext()));
        adapter = new RebirthAdapter(rebirthList);
        rvRebirthNodes.setAdapter(adapter);

        swipeRefresh.setOnRefreshListener(this::loadRebirthData);

        loadRebirthData();

        return view;
    }

    private void loadRebirthData() {
        swipeRefresh.setRefreshing(true);

        ApiClient.get("user/rebirths.php", sessionManager.getToken(), new ApiClient.ApiCallback() {
            @Override
            public void onSuccess(JSONObject response) {
                if (!isAdded()) return;
                swipeRefresh.setRefreshing(false);

                if (response.optBoolean("success")) {
                    JSONObject data = response.optJSONObject("data");
                    if (data != null) {
                        int total = data.optInt("total_rebirths_earned", 0);
                        tvTotalRebirthsEarned.setText(total + " Total Rebirth Positions");

                        JSONArray rArray = data.optJSONArray("rebirth_positions");
                        rebirthList.clear();
                        if (rArray != null) {
                            for (int i = 0; i < rArray.length(); i++) {
                                JSONObject rObj = rArray.optJSONObject(i);
                                if (rObj != null) {
                                    rebirthList.add(new RebirthNode(
                                            rObj.optString("member_id"),
                                            rObj.optString("sponsor_id"),
                                            rObj.optString("placement_parent_id"),
                                            rObj.optString("name"),
                                            rObj.optString("status")
                                    ));
                                }
                            }
                        }
                        adapter.notifyDataSetChanged();
                    }
                } else {
                    Toast.makeText(requireContext(), response.optString("message", "Failed to load rebirths"), Toast.LENGTH_SHORT).show();
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
}
